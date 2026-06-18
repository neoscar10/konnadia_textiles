<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\CustomerActivityLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class CustomerActivityLogService
{
    /**
     * Record a new customer activity log.
     */
    public function record(Customer $customer, string $event, array $payload = []): CustomerActivityLog
    {
        $actorId = $payload['actor_user_id'] ?? Auth::id();
        $title = $payload['title'] ?? $this->getDefaultTitle($event);
        $description = $payload['description'] ?? $this->getDefaultDescription($event, $payload);
        
        $subject = $payload['subject'] ?? null;
        $metadata = $payload['metadata'] ?? null;

        // Gracefully handle console/seeded contexts where request() may be unavailable
        $ipAddress = null;
        $userAgent = null;
        try {
            if (request()) {
                $ipAddress = request()->ip();
                $userAgent = request()->userAgent();
            }
        } catch (\Throwable $e) {
            // Ignore if request context is not available
        }

        $log = new CustomerActivityLog([
            'customer_id' => $customer->id,
            'actor_user_id' => $actorId,
            'event' => $event,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        if ($subject) {
            $log->subject()->associate($subject);
        }

        $log->save();

        return $log;
    }

    /**
     * List paginated activity logs for a customer.
     */
    public function list(Customer $customer, array $filters = [], int $perPage = 5)
    {
        $query = $customer->activityLogs()->with('actor')->orderBy('created_at', 'desc');
        return $query->paginate($perPage);
    }

    /**
     * Get the recent activity logs for a customer.
     */
    public function recent(Customer $customer, int $limit = 5): Collection
    {
        return $customer->activityLogs()
            ->with('actor')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Format a single activity log for visual presentation.
     */
    public function format(CustomerActivityLog $log): array
    {
        return [
            'id' => $log->id,
            'event' => $log->event,
            'title' => $log->title,
            'description' => $log->description,
            'actor' => $log->actor ? $log->actor->name : 'System',
            'date_time' => $log->created_at ? $log->created_at->format('d M Y, h:i A') : 'N/A',
            'icon' => $this->getEventIcon($log->event),
            'metadata' => $log->metadata,
        ];
    }

    /**
     * Format a collection of activity logs.
     */
    public function formatCollection(Collection $logs): array
    {
        return $logs->map(fn($log) => $this->format($log))->toArray();
    }

    /**
     * Get default title based on event.
     */
    protected function getDefaultTitle(string $event): string
    {
        return ucwords(str_replace('_', ' ', $event));
    }

    /**
     * Get default description based on event.
     */
    protected function getDefaultDescription(string $event, array $payload): string
    {
        $actorName = 'System';
        if (isset($payload['actor_user_id'])) {
            $user = \App\Models\User::find($payload['actor_user_id']);
            if ($user) {
                $actorName = $user->name;
            }
        } elseif (Auth::check()) {
            $actorName = Auth::user()->name;
        }

        switch ($event) {
            case 'customer_created':
                return "Customer profile was created by {$actorName}.";
            case 'customer_updated':
                return "Customer profile was updated by {$actorName}.";
            case 'customer_activated':
                return "Customer profile was activated by {$actorName}.";
            case 'customer_deactivated':
                return "Customer profile was deactivated by {$actorName}.";
            case 'customer_deleted':
                return "Customer profile was deleted by {$actorName}.";
            case 'login':
                return "Customer logged in.";
            default:
                return "Activity recorded: {$event}.";
        }
    }

    /**
     * Get the Material Symbols icon name for an event.
     */
    protected function getEventIcon(string $event): string
    {
        return match ($event) {
            'customer_created' => 'person_add',
            'customer_updated' => 'edit_note',
            'customer_activated' => 'check_circle',
            'customer_deactivated' => 'block',
            'customer_deleted' => 'delete',
            'login' => 'login',
            'logout' => 'logout',
            'password_changed' => 'lock_reset',
            'password_generated' => 'key',
            'order_submitted' => 'shopping_cart',
            'credit_order_submitted' => 'credit_score',
            'order_under_review' => 'rate_review',
            'order_approved' => 'assignment_turned_in',
            'order_rejected' => 'cancel',
            'order_dispatched' => 'local_shipping',
            'manual_payment_receipt_uploaded' => 'upload_file',
            'credit_limit_updated' => 'currency_rupee',
            'payment_recorded' => 'payments',
            'outstanding_adjusted' => 'account_balance_wallet',
            'credit_hold_applied' => 'pause_circle',
            'credit_hold_released' => 'play_circle',
            'credit_privilege_enabled' => 'verified',
            'credit_privilege_disabled' => 'unpublished',
            'cart_item_added', 'cart_item_updated', 'cart_item_removed', 'cart_cleared' => 'shopping_bag',
            default => 'info',
        };
    }
}
