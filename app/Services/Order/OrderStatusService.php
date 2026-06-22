<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\User;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\DB;

class OrderStatusService
{
    /**
     * Map of valid transitions.
     */
    protected array $transitions = [
        'draft' => ['submitted'],
        'submitted' => ['under_review', 'approved', 'rejected'],
        'pending_payment_verification' => ['under_review', 'rejected'],
        'under_review' => ['pending_approval', 'approved', 'rejected'],
        'pending_approval' => ['approved', 'rejected'],
        'approved' => ['dispatched', 'cancelled'],
        'rejected' => [],
        'dispatched' => [],
        'cancelled' => [],
    ];

    /**
     * Determine if transition is allowed.
     */
    public function canTransition(Order $order, string $toStatus): bool
    {
        $current = $order->status;

        // Custom validation: if pending payment verification, we can only approve if payment is verified
        if ($current === 'pending_payment_verification' && $toStatus === 'approved') {
            return $order->payment_status === 'verified';
        }

        // Custom validation: cannot dispatch if not approved
        if ($toStatus === 'dispatched' && $current !== 'approved') {
            return false;
        }

        // Allow cancellation from any status except cancelled or rejected
        if ($toStatus === 'cancelled') {
            return !in_array($current, ['cancelled', 'rejected'], true);
        }

        $allowed = $this->transitions[$current] ?? [];
        
        // Let's also support going to approved directly if payment is verified from pending_payment_verification
        if ($current === 'pending_payment_verification' && $toStatus === 'approved' && $order->payment_status === 'verified') {
            return true;
        }

        return in_array($toStatus, $allowed, true);
    }

    /**
     * Perform transition and write status history.
     */
    public function transition(
        Order $order,
        string $toStatus,
        ?User $changedBy = null,
        ?string $note = null,
        array $metadata = []
    ): Order {
        if (!$this->canTransition($order, $toStatus)) {
            throw new \InvalidArgumentException("Invalid status transition from {$order->status} to {$toStatus}.");
        }

        return DB::transaction(function () use ($order, $toStatus, $changedBy, $note, $metadata) {
            $fromStatus = $order->status;
            $order->status = $toStatus;

            // Set timestamps based on status
            if ($toStatus === 'approved') {
                $order->approved_at = now();
            } elseif ($toStatus === 'rejected') {
                $order->rejected_at = now();
            } elseif ($toStatus === 'dispatched') {
                $order->dispatched_at = now();
            }

            $order->save();

            // Record history
            $order->statusHistories()->create([
                'changed_by' => $changedBy?->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'note' => $note,
                'metadata' => $metadata,
            ]);

            return $order;
        });
    }

    /**
     * Get list of allowed actions for admin or customer actor.
     */
    public function getAllowedActions(Order $order, User $actor): array
    {
        $actions = [];
        $isAdmin = $actor->hasRole('super_admin') || $actor->hasRole('admin');

        if ($isAdmin) {
            if ($this->canTransition($order, 'under_review')) {
                $actions[] = 'under_review';
            }
            if ($this->canTransition($order, 'approved')) {
                // If manual payment, must verify receipt first
                if ($order->checkout_method === 'manual_payment' && $order->payment_status !== 'verified') {
                    // Verification action is available instead of approval
                    $actions[] = 'verify_receipt';
                } else {
                    $actions[] = 'approve';
                }
            }
            if ($this->canTransition($order, 'rejected')) {
                $actions[] = 'reject';
            }
            if ($this->canTransition($order, 'dispatched')) {
                $actions[] = 'dispatch';
            }
            if ($this->canTransition($order, 'cancelled')) {
                $actions[] = 'cancel';
            }
        } else {
            // Customer portal actions
            if ($order->status === 'approved' && $this->canTransition($order, 'cancelled')) {
                $actions[] = 'cancel';
            }
        }

        return $actions;
    }

    /**
     * Get label for status.
     */
    public function getStatusLabel(string $status): string
    {
        $enum = OrderStatus::tryFrom($status);
        return $enum ? $enum->label() : ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Get Tailwind CSS badge style classes for status.
     */
    public function getStatusBadge(string $status): array
    {
        $badge = match ($status) {
            'draft' => [
                'bg' => 'bg-slate-50 border border-slate-200',
                'text' => 'text-slate-700',
                'label' => 'Draft',
                'type' => 'neutral'
            ],
            'submitted' => [
                'bg' => 'bg-blue-50 border border-blue-200',
                'text' => 'text-blue-700',
                'label' => 'Submitted',
                'type' => 'info'
            ],
            'under_review' => [
                'bg' => 'bg-amber-50 border border-amber-200',
                'text' => 'text-amber-700',
                'label' => 'Under Review',
                'type' => 'warning'
            ],
            'pending_approval' => [
                'bg' => 'bg-orange-50 border border-orange-200',
                'text' => 'text-orange-700',
                'label' => 'Pending Approval',
                'type' => 'warning'
            ],
            'pending_payment_verification' => [
                'bg' => 'bg-yellow-50 border border-yellow-200',
                'text' => 'text-yellow-800',
                'label' => 'Pending Payment Verification',
                'type' => 'warning'
            ],
            'approved' => [
                'bg' => 'bg-emerald-50 border border-emerald-200',
                'text' => 'text-emerald-700',
                'label' => 'Approved',
                'type' => 'success'
            ],
            'rejected' => [
                'bg' => 'bg-rose-50 border border-rose-200',
                'text' => 'text-rose-700',
                'label' => 'Rejected',
                'type' => 'danger'
            ],
            'dispatched' => [
                'bg' => 'bg-purple-50 border border-purple-200',
                'text' => 'text-purple-700',
                'label' => 'Dispatched',
                'type' => 'primary'
            ],
            'cancelled' => [
                'bg' => 'bg-zinc-50 border border-zinc-200',
                'text' => 'text-zinc-700',
                'label' => 'Cancelled',
                'type' => 'neutral'
            ],
            default => [
                'bg' => 'bg-slate-50 border border-slate-200',
                'text' => 'text-slate-700',
                'label' => ucfirst(str_replace('_', ' ', $status)),
                'type' => 'neutral'
            ],
        };

        $badge['value'] = $status;

        return $badge;
    }
}
