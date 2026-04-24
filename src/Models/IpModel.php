<?php

declare(strict_types=1);

namespace App\Models;

use Medoo\Medoo;
use Psr\Log\LoggerInterface;

class IpModel
{
    private Medoo $db;
    private LoggerInterface $logger;
    private string $tableTrack = 'track_ips';
    private string $tableFails = 'ip_fails';

    public const STATUS_NORMAL   = 1;
    public const STATUS_ALLOW    = 2;
    public const STATUS_DISABLED = 3;

    public function __construct(Medoo $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Check if IP is blocked (status 3).
     */
    public function isBlocked(string $ip): bool
    {
        $statusId = $this->db->get($this->tableTrack, 'ip_status_id', ['remote_addr' => $ip]);
        return (int)$statusId === self::STATUS_DISABLED;
    }

    /**
     * Get current IP status name.
     */
    public function getStatus(string $ip): string
    {
        $status = $this->db->get($this->tableTrack, [
            "[>]ip_statuses" => ["ip_status_id" => "id"]
        ], "ip_statuses.name", ["remote_addr" => $ip]);

        return $status ?: 'normal';
    }

    /**
     * Set IP status manually (Admin).
     */
    public function setStatus(string $ip, int $statusId, int $adminUserId): bool
    {
        $oldStatus = $this->getStatus($ip);
        $statusNames = [1 => 'normal', 2 => 'allow', 3 => 'disabled'];
        $newStatusName = $statusNames[$statusId] ?? 'unknown';

        $exists = $this->db->has($this->tableTrack, ['remote_addr' => $ip]);
        
        if ($exists) {
            $this->db->update($this->tableTrack, [
                'ip_status_id' => $statusId,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['remote_addr' => $ip]);
        } else {
            $this->db->insert($this->tableTrack, [
                'remote_addr' => $ip,
                'ip_status_id' => $statusId,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        $this->logger->info(sprintf(
            'SECURITY: Admin ID %d changed IP %s status from %s to %s',
            $adminUserId, $ip, $oldStatus, $newStatusName
        ));

        return true;
    }

    /**
     * Log 404 fail and auto-block if limit reached.
     */
    public function logFail(string $ip, int $limit, int $intervalMinutes): void
    {
        // 1. Skip if IP is already in allow list
        $statusId = $this->db->get($this->tableTrack, 'ip_status_id', ['remote_addr' => $ip]);
        if ((int)$statusId === self::STATUS_ALLOW) {
            return;
        }

        // 2. Find fail record
        $fail = $this->db->get($this->tableFails, '*', ['remote_addr' => $ip]);
        $now = time();

        if (!$fail) {
            // First fail
            $this->db->insert($this->tableFails, [
                'remote_addr' => $ip,
                'fail_count' => 1,
                'first_fail_at' => date('Y-m-d H:i:s')
            ]);
            return;
        }

        $firstFailTime = strtotime($fail['first_fail_at']);
        $diffSeconds = $now - $firstFailTime;

        if ($diffSeconds > ($intervalMinutes * 60)) {
            // Window expired, reset
            $this->db->update($this->tableFails, [
                'fail_count' => 1,
                'first_fail_at' => date('Y-m-d H:i:s')
            ], ['remote_addr' => $ip]);
        } else {
            // Within window, increment
            $newCount = (int)$fail['fail_count'] + 1;
            
            if ($newCount >= $limit) {
                // BLOCK IP
                $this->autoBlock($ip, $newCount);
            } else {
                $this->db->update($this->tableFails, [
                    'fail_count' => $newCount
                ], ['remote_addr' => $ip]);
            }
        }
    }

    private function autoBlock(string $ip, int $count): void
    {
        // Update/Insert into track_ips
        $exists = $this->db->has($this->tableTrack, ['remote_addr' => $ip]);
        if ($exists) {
            $this->db->update($this->tableTrack, [
                'ip_status_id' => self::STATUS_DISABLED,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['remote_addr' => $ip]);
        } else {
            $this->db->insert($this->tableTrack, [
                'remote_addr' => $ip,
                'ip_status_id' => self::STATUS_DISABLED,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        // Clean up fails
        $this->db->delete($this->tableFails, ['remote_addr' => $ip]);

        // Log ALERT
        $this->logger->alert(sprintf('SECURITY ALERT: IP %s auto-blocked after %d fails', $ip, $count));
    }

    public function getAllTrackedIps(): array
    {
        return $this->db->select($this->tableTrack, [
            "[>]ip_statuses" => ["ip_status_id" => "id"]
        ], [
            "track_ips.remote_addr",
            "ip_statuses.name(status)",
            "track_ips.updated_at"
        ]);
    }
}
