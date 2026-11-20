<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Distribution;
use App\Models\Farmer;
use App\Models\PcicTrackingUpdate;
use App\Models\SmsNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class FarmerNotifications
{
    public function unreadCount(Farmer $farmer): int
    {
        $count = 0;

        foreach ($this->sources($farmer) as $source => [$query]) {
            $count += $query->whereNotExists($this->reads($farmer, $source, $query))->count();
        }

        return $count;
    }

    public function feed(Farmer $farmer, bool $markRead): array
    {
        $items = [];
        $receipts = [];

        foreach ($this->sources($farmer) as $source => [$query, $order, $limit]) {
            $reads = $this->reads($farmer, $source, $query);
            $items[$source] = $query->select($query->getModel()->getTable().'.*')
                ->orderByRaw('CASE WHEN EXISTS ('.$reads->toSql().') THEN 1 ELSE 0 END', $reads->getBindings())
                ->latest($order)
                ->take($limit)
                ->get();

            foreach ($items[$source] as $item) {
                $receipts[] = [
                    'farmer_id' => $farmer->id,
                    'source' => $source,
                    'notification_id' => $item->getKey(),
                ];
            }
        }

        if ($markRead && $receipts) {
            DB::table('farmer_notification_reads')->insertOrIgnore($receipts);
        }

        return $items;
    }

    private function sources(Farmer $farmer): array
    {
        return [
            'notifications' => [
                SmsNotification::where('farmer_id', $farmer->id), 'sent_at', 10,
            ],
            'announcements' => [
                Announcement::where('is_published', true)->where('publish_date', '<=', now()),
                'publish_date', 5,
            ],
            'distributions' => [
                Distribution::with(['program', 'benefit'])->where('farmer_id', $farmer->id),
                'distributed_at', 5,
            ],
            'pcicUpdates' => [
                PcicTrackingUpdate::whereHas('application', fn ($query) => $query->where('farmer_id', $farmer->id))
                    ->with('application.benefit'),
                'id', 10,
            ],
        ];
    }

    private function reads(Farmer $farmer, string $source, Builder $query): QueryBuilder
    {
        return DB::table('farmer_notification_reads')
            ->selectRaw('1')
            ->where('farmer_id', $farmer->id)
            ->where('source', $source)
            ->whereColumn('notification_id', $query->getModel()->getQualifiedKeyName());
    }
}
