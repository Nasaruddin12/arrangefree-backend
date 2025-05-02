<?php

namespace App\Validation;

class CustomRules
{
    public function check_age($dob, string $params = null): bool
    {
        try {
            $birthDate = new \DateTime($dob);
            $today     = new \DateTime();
            $age       = $today->diff($birthDate)->y;

            return $age >= 18;
        } catch (\Exception $e) {
            return false;
        }
    }
}
