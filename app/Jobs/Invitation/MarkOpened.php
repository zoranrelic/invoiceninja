<?php
/**
 * Invoice Ninja (https://invoiceninja.com)
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2020. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Jobs\Invitation;

use App\Models\Invoice;
use App\Models\Payment;
use App\Repositories\InvoiceRepository;
use App\Utils\Traits\NumberFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

//todo - ensure we are MultiDB Aware in dispatched jobs

class MarkOpened implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, NumberFormatter;

    public $message_id;

    public $entity;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $message_id, string $entity)
    {
        $this->message_id = $message_id;

        $this->entity = $entity;
    }

    /**
     * Execute the job.
     *
     *
     * @return void
     */
    public function handle()
    {
        $invitation = $this->entity::with('user', 'contact')
                        ->whereMessageId($this->message_id)
                        ->first();

        if (! $invitation) {
            return false;
        }

        $invitation->email_error = $error;
        $invitation->save();
    }
}
