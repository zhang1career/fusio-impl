<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2018 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Fusio\Impl\Service\Plan;

use Fusio\Engine\Model\TransactionInterface;
use Fusio\Impl\Authorization\UserContext;
use Fusio\Impl\Event\Plan\Invoice\CreatedEvent;
use Fusio\Impl\Event\Plan\Invoice\DeletedEvent;
use Fusio\Impl\Event\Plan\Invoice\PayedEvent;
use Fusio\Impl\Event\Plan\Invoice\UpdatedEvent;
use Fusio\Impl\Event\Plan\InvoiceEvents;
use Fusio\Impl\Table;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use PSX\Http\Exception as StatusCode;

/**
 * Invoice
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class Invoice
{
    /**
     * @var \Fusio\Impl\Table\Plan\Contract
     */
    private $contractTable;

    /**
     * @var \Fusio\Impl\Table\Plan\Invoice
     */
    private $invoiceTable;

    /**
     * @var \Fusio\Impl\Table\User
     */
    private $userTable;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param \Fusio\Impl\Table\Plan\Contract $contractTable
     * @param \Fusio\Impl\Table\Plan\Invoice $invoiceTable
     * @param \Fusio\Impl\Table\User $userTable
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     */
    public function __construct(Table\Plan\Contract $contractTable, Table\Plan\Invoice $invoiceTable, Table\User $userTable, EventDispatcherInterface $eventDispatcher)
    {
        $this->contractTable = $contractTable;
        $this->invoiceTable = $invoiceTable;
        $this->userTable = $userTable;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Creates an invoice for the provided contract id
     * 
     * @param integer $contractId
     * @param \DateTime $startDate
     * @param \Fusio\Impl\Authorization\UserContext $context
     */
    public function create($contractId, \DateTime $startDate, UserContext $context)
    {
        $contract = $this->contractTable->get($contractId);
        if (empty($contract)) {
            throw new \InvalidArgumentException('Invalid contract id');
        }

        $from = (clone $startDate)->setTime(0, 0, 0);
        $to   = (new DateCalculator())->calculate($from, $contract['period']);

        $record = [
            'contract_id' => $contract['id'],
            'transaction_id' => null,
            'prev_id' => null,
            'status' => Table\Plan\Invoice::STATUS_OPEN,
            'amount' => $contract['amount'],
            'points' => $contract['points'],
            'from_date' => $from,
            'to_date' => $to,
            'pay_date' => null,
            'insert_date' => new \DateTime(),
        ];

        $this->invoiceTable->create($record);

        $invoiceId = $this->invoiceTable->getLastInsertId();

        $this->eventDispatcher->dispatch(InvoiceEvents::CREATE, new CreatedEvent($contractId, $record, $context));

        return $invoiceId;
    }

    public function update($invoiceId, $status, UserContext $context)
    {
        $invoice = $this->invoiceTable->get($invoiceId);

        if (empty($invoice)) {
            throw new StatusCode\NotFoundException('Could not find invoice');
        }

        if ($invoice['status'] == Table\Plan\Invoice::STATUS_DELETED) {
            throw new StatusCode\GoneException('Invoice was deleted');
        }

        // update invoice
        $record = [
            'id'     => $invoice['id'],
            'status' => $status,
        ];

        $this->invoiceTable->update($record);

        $this->eventDispatcher->dispatch(InvoiceEvents::UPDATE, new UpdatedEvent($invoiceId, $record, $invoice, $context));
    }

    public function delete($invoiceId, UserContext $context)
    {
        $invoice = $this->invoiceTable->get($invoiceId);

        if (empty($invoice)) {
            throw new StatusCode\NotFoundException('Could not find invoice');
        }

        $record = [
            'id'     => $invoice['id'],
            'status' => Table\Plan\Invoice::STATUS_DELETED,
        ];

        $this->invoiceTable->update($record);

        $this->eventDispatcher->dispatch(InvoiceEvents::DELETE, new DeletedEvent($invoiceId, $invoice, $context));
    }

    /**
     * Marks the invoice which is referenced by the transaction as payed. This
     * also credits the points from the invoice to the account of the user
     * 
     * @param \Fusio\Engine\Model\TransactionInterface $transaction
     */
    public function pay(TransactionInterface $transaction)
    {
        $invoice = $this->invoiceTable->get($transaction->getInvoiceId());
        if (empty($invoice)) {
            throw new \InvalidArgumentException('Invalid invoice id');
        }

        if ($invoice['status'] == Table\Plan\Invoice::STATUS_PAYED) {
            throw new \InvalidArgumentException('Invoice already payed');
        }

        if ($transaction->getStatus() != TransactionInterface::STATUS_APPROVED) {
            throw new \InvalidArgumentException('Cant mark invoice as payed since the transaction is not approved');
        }

        // mark invoice as payed
        $this->invoiceTable->update([
            'id' => $invoice['id'],
            'transaction_id' => $transaction->getId(),
            'status' => Table\Plan\Invoice::STATUS_PAYED,
            'pay_date' => new \DateTime(),
        ]);

        // credit points
        $this->userTable->creditPoints($invoice['user_id'], $invoice['points']);

        // dispatch payed event
        $context = UserContext::newContext($invoice['user_id']);
        $this->eventDispatcher->dispatch(InvoiceEvents::PAYED, new PayedEvent($invoice['id'], $invoice, $transaction, $context));
    }
}
