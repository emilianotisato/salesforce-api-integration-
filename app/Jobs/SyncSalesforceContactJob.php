<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Services\SalesforceApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncSalesforceContactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $contact;

    public $type;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Contact $contact, string $type)
    {
        $this->contact = $contact;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(SalesforceApi $salesforce)
    {
        switch ($this->type) {
            case 'created':
                $results = $salesforce->module('contacts')->create($this->contact->toArray());
                $this->contact->update(['salesforce_id' => $results->id]);
                break;

            case 'updated':
                $salesforce->module('contacts')->update($this->contact->salesforce_id, $this->contact->toArray());
                break;

            case 'deleted':
                $salesforce->module('contacts')->delete($this->contact->salesforce_id);
                break;
        }
    }
}
