<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2020 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Impl\Console\System;

use Fusio\Impl\Service;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CronjobFlushCommand
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class CronjobFlushCommand extends Command
{
    /**
     * @var \Fusio\Impl\Service\Cronjob
     */
    protected $cronjobService;

    public function __construct(Service\Cronjob $cronjobService)
    {
        parent::__construct();

        $this->cronjobService = $cronjobService;
    }

    protected function configure()
    {
        $this
            ->setName('system:cronjob_flush')
            ->setDescription('Writes all available cronjobs to the cron file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cronjobService->flush();

        $output->writeln('Flush successful');

        return 0;
    }
}
