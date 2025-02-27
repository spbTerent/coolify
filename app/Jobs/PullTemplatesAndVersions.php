<?php

namespace App\Jobs;

use App\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class PullTemplatesAndVersions implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 10;

    public function __construct()
    {
    }
    public function handle(): void
    {
        try {
            if (!isDev() && !isCloud()) {
                ray('PullTemplatesAndVersions versions.json');
                $response = Http::retry(3, 1000)->get('https://cdn.coollabs.io/coolify/versions.json');
                if ($response->successful()) {
                    $versions = $response->json();
                    File::put(base_path('versions.json'), json_encode($versions, JSON_PRETTY_PRINT));
                } else {
                    send_internal_notification('PullTemplatesAndVersions failed with: ' . $response->status() . ' ' . $response->body());
                }
            }
        } catch (\Throwable $e) {
            send_internal_notification('PullTemplatesAndVersions failed with: ' . $e->getMessage());
            ray($e->getMessage());
        }
        try {
            if (!isDev()) {
                ray('PullTemplatesAndVersions service-templates');
                $response = Http::retry(3, 1000)->get(config('constants.services.official'));
                if ($response->successful()) {
                    $services = $response->json();
                    File::put(base_path('templates/service-templates.json'), json_encode($services));
                } else {
                    send_internal_notification('PullTemplatesAndVersions failed with: ' . $response->status() . ' ' . $response->body());
                }
            }
        } catch (\Throwable $e) {
            send_internal_notification('PullTemplatesAndVersions failed with: ' . $e->getMessage());
            ray($e->getMessage());
        }
    }
}
