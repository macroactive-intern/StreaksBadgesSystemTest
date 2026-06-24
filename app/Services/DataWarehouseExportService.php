<?php

namespace App\Services;

use App\Models\ActivityEvent;
use App\Models\AnalyticsEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DataWarehouseExportService
{
    /**
     * 15.3 — Batch export analytics_events for a creator app since a given UTC datetime.
     * Returns a flat array of records; the caller decides transport (HTTP push, S3, queue, etc.).
     * If config('analytics.data_warehouse_endpoint') is set, ships the batch automatically.
     */
    public function exportAnalyticsEvents(int $creatorAppId, string $since, ?string $until = null): array
    {
        $query = AnalyticsEvent::where('creator_app_id', $creatorAppId)
            ->where('occurred_at', '>=', $since);

        if ($until !== null) {
            $query->where('occurred_at', '<=', $until);
        }

        $records = $query->orderBy('occurred_at')->get()->map(fn ($e) => [
            'id'             => $e->id,
            'creator_app_id' => $e->creator_app_id,
            'user_id'        => $e->user_id,
            'event_type'     => $e->event_type,
            'payload'        => $e->payload,
            'occurred_at'    => $e->occurred_at?->toIso8601String(),
        ])->all();

        $this->shipIfConfigured('analytics_events', $creatorAppId, $records);

        return $records;
    }

    /**
     * 15.3 — Batch export activity_events for a creator app since a given date string.
     */
    public function exportActivityEvents(int $creatorAppId, string $since, ?string $until = null): array
    {
        $query = ActivityEvent::where('creator_app_id', $creatorAppId)
            ->where('local_event_date', '>=', $since);

        if ($until !== null) {
            $query->where('local_event_date', '<=', $until);
        }

        $records = $query->orderBy('local_event_date')->get()->map(fn ($e) => [
            'id'                  => $e->id,
            'creator_app_id'      => $e->creator_app_id,
            'user_id'             => $e->user_id,
            'event_type'          => $e->event_type,
            'event_timestamp_utc' => $e->event_timestamp_utc?->toIso8601String(),
            'user_timezone'       => $e->user_timezone,
            'local_event_date'    => $e->local_event_date,
            'metadata'            => $e->metadata,
            'source_type'         => $e->source_type,
            'source_id'           => $e->source_id,
            'revoked_at'          => $e->revoked_at?->toIso8601String(),
        ])->all();

        $this->shipIfConfigured('activity_events', $creatorAppId, $records);

        return $records;
    }

    /**
     * If a data warehouse endpoint is configured, POST the batch there.
     * Swallows all exceptions — DWH export must not interrupt the request path.
     */
    private function shipIfConfigured(string $table, int $creatorAppId, array $records): void
    {
        $endpoint = config('analytics.data_warehouse_endpoint');

        if (!$endpoint || empty($records)) {
            return;
        }

        try {
            $client = new \Illuminate\Http\Client\PendingRequest();
            $client->post($endpoint, [
                'table'          => $table,
                'creator_app_id' => $creatorAppId,
                'records'        => $records,
            ]);
        } catch (\Throwable $e) {
            Log::warning('data_warehouse_export_failed', [
                'table'          => $table,
                'creator_app_id' => $creatorAppId,
                'record_count'   => count($records),
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
