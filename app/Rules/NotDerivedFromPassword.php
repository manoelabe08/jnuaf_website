<?php



namespace App\Rules;



use Closure;

use Illuminate\Contracts\Validation\ValidationRule;



class NotDerivedFromPassword implements ValidationRule

{

    public function __construct(private string $old, private int $minShared = 4) {}



    public function validate(string $attribute, mixed $value, Closure $fail): void

    {

        $old = mb_strtolower($this->old);

        $new = mb_strtolower((string) $value);



        if ($old === '' || $new === '') {

            return;

        }



        if ($new === $old) {

            $fail('Le nouveau mot de passe doit être différent de l’ancien.');

            return;

        }



        if (self::longestCommon($old, $new) >= $this->minShared) {

            $fail('Le nouveau mot de passe ne doit reprendre aucune suite de '.$this->minShared.' caractères (ou plus) de l’ancien.');

        }

    }



    public static function longestCommon(string $a, string $b): int

    {

        $x = mb_str_split($a);

        $y = mb_str_split($b);

        $best = 0;

        $prev = array_fill(0, count($y) + 1, 0);



        foreach ($x as $cx) {

            $cur = array_fill(0, count($y) + 1, 0);

            foreach ($y as $j => $cy) {

                if ($cx === $cy) {

                    $cur[$j + 1] = $prev[$j] + 1;

                    if ($cur[$j + 1] > $best) {

                        $best = $cur[$j + 1];

                    }

                }

            }

            $prev = $cur;

        }



        return $best;

    }

}

