# Fusio 经网关代理大二进制响应（如 CDN 图片）修复计划

## 背景摘要

- 经 Fusio HTTP Action 代理 PNG 等大二进制时，可能出现 **body 截断**、`curl: (18)`、体积极小（例如约 78KB 对比完整约 2.4MB）。
- **本次线上截断的直接原因（已定位）**：**nginx** 在 `fastcgi` 响应超过缓冲时需写入 **`/var/lib/nginx/tmp/`**（或配置的 `fastcgi_temp_path`），**nginx worker 用户对该目录无写权限**时出现 **Permission denied**，导致只发出前一段响应。
- **PSX `StringStream` 曾使用 `mb_*`**：对二进制 HTTP body 语义错误（字节长度与「字符长度」不一致），属于 **应修复的独立问题**；与 nginx 权限问题 **并存时不叠加依赖**，但 **字节语义修复仍建议保留**（fork / 补丁已实施的可标记为完成）。
- Operation 的 **`outgoing` 字段不能解决上述问题**（其为 Schema 引用，且不改变写出路径）。

---

## 阶段 A：最小改动（`StringStream` 字节语义）— **已完成**

以下已在 **psx-http fork**（如 `zhang1career/psx-http`）等路径落地，无需再作为待办执行。

| 项 | 说明 |
|----|------|
| **内容** | `StringStream` 使用 **`strlen` / `substr`** 替代 **`mb_*`**，按字节处理 HTTP body。 |
| **文件** | `vendor/psx/http/src/Stream/StringStream.php`（及配套单测调整）。 |
| **持久化** | 通过 **Composer VCS / patch** 指向下游 fork，避免直接长期改 vendor。 |
| **验证** | 大 PNG / octet-stream：`getSize` 与真实字节数一致；JSON/文本接口回归无异常。 |

---

## 阶段 B：更大范围的修法（架构与运维，按需）

### B1. 大文件不经由 Fusio 搬运 body（推荐业务策略）

| 项 | 说明 |
|----|------|
| **思路** | 网关在 Fusio 只做鉴权/计费/路由，**不代理整文件字节**。 |
| **做法示例** | Operation 返回 **302/307** 到 CDN 签名 URL；或客户端在鉴权后**直连 CDN**。 |
| **收益** | 规避内存与 PHP 执行时间峰值、降低网关带宽与故障面。 |

### B2. HTTP Action 侧：流式上游、避免整段载入内存

| 项 | 说明 |
|----|------|
| **文件** | `vendor/fusio/adapter-http/src/Action/HttpSenderAbstract.php`（及与 `Factory::build`、响应类型相关的衔接） |
| **思路** | Guzzle 使用 **stream 响应**，将 `Psr\Http\Message\StreamInterface` 传入引擎响应，而非 `(string) $response->getBody()` 一次性读入。 |
| **依赖** | 引擎与 `ResponseWriter` 需能识别 **Stream 类型 body**（`ResponseWriter` 已对 `StreamInterface` 有分支，需打通全链路）。 |
| **收益** | 降低 `memory_limit` 风险，适合更大对象。 |

### B3. 框架层：二进制响应专用写出路径

| 项 | 说明 |
|----|------|
| **文件** | `vendor/psx/framework/src/Http/ResponseWriter.php` |
| **思路** | 当 `Content-Type` 为二进制族或 body 为原始字节流时，优先走 **`HttpWriter\Stream`** 等路径，减少大字符串在内存中的重复拼接（按需）。 |
| **收益** | 语义更清晰；与 A 阶段字节安全 **可互补**。 |

### B4. 观测与运维

| 项 | 说明 |
|----|------|
| **日志** | 临时提高 `LOG_LEVEL`；必要时在适配器层记录出站 URL、状态码（勿记录敏感 body）。 |
| **nginx** | `/api/cdn/`、`/api/oss/` 不被静态后缀 `location` 抢走；`fastcgi_buffers` / `fastcgi_read_timeout` 与大响应匹配；**保证 `fastcgi` 临时目录（如 `/var/lib/nginx/tmp/`）对 nginx worker 用户可写**，磁盘空间充足。 |

---

## 建议执行顺序（更新后）

1. ~~**A1 + A2**~~：**已完成**（若尚未合并到所有环境，仅做发布/同步）。  
2. **运维**：确认 **fastcgi 临时目录权限与磁盘**（与本次截断直接相关）。  
3. 按业务量评估 **B1**（重定向/直连 CDN）。  
4. 若需代理 **超大** 对象且内存吃紧，再评估 **B2 / B3**。  

---

## 参考路径（vendor 相对位置示例）

- `vendor/psx/http/src/Stream/StringStream.php` — 字节语义（阶段 A，已完成）  
- `vendor/psx/framework/src/Http/ResponseWriter.php` — 更大范围写出策略（按需）  
- `vendor/fusio/adapter-http/src/Action/HttpSenderAbstract.php` — 上游流式与 body 构造（按需）  

---

*文档：Fusio 经网关代理 CDN 二进制响应问题规划；截断根因含 nginx fastcgi 临时目录权限；`StringStream` 字节修复为独立正确性改进。*
