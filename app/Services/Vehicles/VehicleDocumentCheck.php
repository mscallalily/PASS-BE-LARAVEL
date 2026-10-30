<?php

namespace App\Services\Vehicles;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * The automatic OR/CR check: read the stored OR and CR and compare them with
 * the submitted details. Runs before a registration is saved (nothing is kept
 * unless it passes) and in ProcessVehicleDocumentCheck.
 */
class VehicleDocumentCheck
{
    public function __construct(private VehicleDocumentReader $reader, private VehicleDocumentFields $parser, private VehicleDocumentRules $rules) {}

    /**
     * @param array{or: ?string, cr: ?string} $paths private-disk paths
     * @param array{plate: ?string, make: ?string, model: ?string, color: ?string, year: ?int, type_code: ?string} $submitted
     */
    public function run(array $paths, array $submitted): array
    {
        $documents = [];
        $fields = [];
        foreach ($paths as $kind => $path) {
            $documents[$kind] = $this->read($path);
            $fields[$kind] = $this->parser->parseDocument($documents[$kind] ?? []);
        }
        return $this->rules->evaluate($documents, $fields, $submitted);
    }

    private function read(?string $path): ?array
    {
        $disk = Storage::disk('private');
        if (!$path || !$disk->exists($path)) return null;
        try {
            return $this->reader->read($disk->path($path), (string) $disk->mimeType($path));
        } catch (Throwable $e) {
            report($e);
            return ['kind' => 'image', 'engine_available' => false, 'engine_error' => 'The document could not be processed.', 'text' => '', 'confidence' => null, 'width' => 1, 'height' => 1, 'sharpness' => null];
        }
    }
}
