<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

abstract class Controller
{
    /** Letters, spaces, apostrophes and @ only. */
    protected function isValidName(?string $name): bool
    {
        return $name !== null && preg_match("/^[A-Za-z\s'@]+$/", trim($name)) === 1;
    }

    /** Subject / package codes: the admin types only "XXXF4" / "XXXF5". */
    protected function isValidCode(string $code): bool
    {
        return preg_match('/^[A-Z0-9]+F[45]$/', $code) === 1;
    }

    /** Malaysian mobile number after normalising, e.g. 0123456789 or 01123456789 (same rule as the forms). */
    protected function isValidPhone(?string $normalisedPhone): bool
    {
        return $normalisedPhone !== null && preg_match('/^01\d{8,9}$/', $normalisedPhone) === 1;
    }

    /**
     * Server-side checks for the account forms (sign up, add admin/teacher/student, admin edit, own profile).
     * Returns the ?error= code to send back, or null when everything is fine.
     */
    protected function accountError(?string $name, ?string $email, ?string $normalisedPhone, ?string $password = null, bool $checkPassword = false): ?string
    {
        return match (true) {
            ! $this->isValidName($name) => 'invalidName',
            ! filter_var((string) $email, FILTER_VALIDATE_EMAIL) => 'invalidEmail',
            ! $this->isValidPhone($normalisedPhone) => 'invalidPhone',
            $checkPassword && ! $this->isStrongPassword($password) => 'weakPassword',
            default => null,
        };
    }

    /** At least 8 characters with an uppercase letter, a lowercase letter and a number (same as the forms). */
    protected function isStrongPassword(?string $password): bool
    {
        return $password !== null && strlen($password) >= 8
            && preg_match('/[A-Z]/', $password) && preg_match('/[a-z]/', $password) && preg_match('/[0-9]/', $password);
    }

    /** The uploaded file, or null when nothing was chosen. */
    protected function uploaded(Request $request, string $field): ?UploadedFile
    {
        $file = $request->file($field);
        if (! $file instanceof UploadedFile || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $file;
    }

    /**
     * Optional image upload check (.jpg, .jpeg, .png only). No file is fine.
     * A file that failed to upload (for example bigger than upload_max_filesize) is rejected.
     */
    protected function isValidOptionalImage(Request $request, string $field): bool
    {
        $file = $this->uploaded($request, $field);
        if ($file === null) {
            return true;
        }

        return $file->isValid() && preg_match('/\.(jpg|jpeg|png)$/i', trim($file->getClientOriginalName())) === 1;
    }

    /** Raw bytes of an uploaded file (stored in a BLOB column), or null. */
    protected function fileBytes(Request $request, string $field): ?string
    {
        $file = $this->uploaded($request, $field);

        return ($file && $file->isValid()) ? file_get_contents($file->getRealPath()) : null;
    }
}
