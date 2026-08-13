<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpRange extends Model
{
    protected $table = 'ip_klienci';

    public $timestamps = false;

    protected $fillable = ['id_klient', 'opis', 'ip1', 'ip2'];

    public function contains(string $ip): bool
    {
        $current = ip2long($ip);
        $from = ip2long((string) $this->ip1);
        $to = ip2long((string) $this->ip2);

        if ($current === false || $from === false || $to === false) {
            return false;
        }

        return $current >= $from && $current <= $to;
    }
}
