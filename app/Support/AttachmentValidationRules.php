<?php

namespace App\Support;

class AttachmentValidationRules
{
    /**
     * @return list<string>
     */
    public static function upload(bool $required = true): array
    {
        $rules = $required ? ['required', 'file'] : ['file'];
        $maxKilobytes = (int) config('support.attachments.max_kilobytes', 20480);
        $allowedExtensions = array_values(array_map(
            fn (string $extension): string => strtolower(trim($extension)),
            config('support.attachments.allowed_extensions', [])
        ));
        $allowedMimes = array_values(array_map(
            fn (string $mime): string => trim($mime),
            config('support.attachments.allowed_mimes', [])
        ));

        $rules[] = 'max:'.$maxKilobytes;

        if ($allowedExtensions !== []) {
            $rules[] = 'extensions:'.implode(',', $allowedExtensions);
        }

        if ($allowedMimes !== []) {
            $rules[] = 'mimetypes:'.implode(',', $allowedMimes);
        }

        return $rules;
    }
}
