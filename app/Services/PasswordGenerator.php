<?php



namespace App\Services;



class PasswordGenerator

{

    private const LOWER = 'abcdefghijkmnopqrstuvwxyz';

    private const UPPER = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

    private const DIGITS = '23456789';

    private const SYMBOLS = '!@#$%*-_=+?';



    public static function generate(int $length = 16): string

    {

        $length = max(12, $length);

        $sets = [self::LOWER, self::UPPER, self::DIGITS, self::SYMBOLS];



        $chars = [];

        foreach ($sets as $set) {

            $chars[] = $set[random_int(0, strlen($set) - 1)];

        }



        $all = implode('', $sets);

        while (count($chars) < $length) {

            $chars[] = $all[random_int(0, strlen($all) - 1)];

        }



        for ($i = count($chars) - 1; $i > 0; $i--) {

            $j = random_int(0, $i);

            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];

        }



        return implode('', $chars);

    }

}

