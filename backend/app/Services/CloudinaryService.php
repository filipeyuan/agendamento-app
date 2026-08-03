<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ImageUploadFailedException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    private const API = 'https://api.cloudinary.com/v1_1';

    /**
     * @return array{url: string, public_id: string}
     */
    public function upload(UploadedFile $file, string $folder = 'zelo'): array
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $timestamp = (string) time();

        $paramsToSign = ['folder' => $folder, 'timestamp' => $timestamp];
        ksort($paramsToSign);
        $toSign = collect($paramsToSign)->map(fn ($value, $key) => "{$key}={$value}")->implode('&');
        $signature = sha1($toSign.config('services.cloudinary.api_secret'));

        $response = Http::attach('file', (string) $file->get(), $file->getClientOriginalName())
            ->post(self::API."/{$cloudName}/image/upload", [
                'api_key' => (string) config('services.cloudinary.api_key'),
                'timestamp' => $timestamp,
                'folder' => $folder,
                'signature' => $signature,
            ]);

        if ($response->failed()) {
            Log::error('Falha ao enviar imagem pro Cloudinary.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new ImageUploadFailedException;
        }

        return [
            'url' => (string) $response->json('secure_url'),
            'public_id' => (string) $response->json('public_id'),
        ];
    }

    public function destroy(string $publicId): void
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $timestamp = (string) time();
        $signature = sha1("public_id={$publicId}&timestamp={$timestamp}".config('services.cloudinary.api_secret'));

        $response = Http::asForm()->post(self::API."/{$cloudName}/image/destroy", [
            'public_id' => $publicId,
            'api_key' => (string) config('services.cloudinary.api_key'),
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);

        if ($response->failed()) {
            Log::warning('Falha ao remover imagem do Cloudinary.', [
                'public_id' => $publicId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }
}
