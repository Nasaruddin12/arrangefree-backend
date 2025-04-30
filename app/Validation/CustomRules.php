<?php

namespace App\Validation;

class CustomRules
{
    public function check_age(string $dob, string $params, array $data): bool
    {
        $birthDate = new \DateTime($dob);
        $today     = new \DateTime();
        $age       = $today->diff($birthDate)->y;

        return $age >= 18;
    }
}
