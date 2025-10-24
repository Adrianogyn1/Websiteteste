<?php

class Helps
{
  /**
  De .NET ticks → PHP datetime
$ticks = '638962316076758500';
$data = Helps::mysqlTicksToDateTime($ticks);
echo $data; // "2025-10-24 22:13:27"

// De PHP datetime → ticks
$agora = new DateTime();
$ticks = Helps::dateTimeToTicks($agora);
echo $ticks; // ex: "638962316076758000"

  */
    /**
     * Converte Ticks (.NET) → Data/Hora (Y-m-d H:i:s)
     */
    public static function mysqlTicksToDateTime(string|int|null $ticks): ?string
    {
        if (empty($ticks) || !is_numeric($ticks)) {
            return null;
        }

        $unixEpochTicks = '621355968000000000';
        $diff = bcsub((string)$ticks, $unixEpochTicks);
        $seconds = bcdiv($diff, '10000000', 0);

        $dt = new DateTime("@$seconds"); // timestamp
        $dt->setTimezone(new DateTimeZone('America/Sao_Paulo'));

        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * Converte Data/Hora → Ticks (.NET)
     */
    public static function dateTimeToTicks(string|DateTime $date): string
    {
        if (!$date instanceof DateTime) {
            $date = new DateTime($date, new DateTimeZone('America/Sao_Paulo'));
        }

        // Normaliza para UTC como .NET faz internamente
        $date->setTimezone(new DateTimeZone('UTC'));

        $seconds = $date->getTimestamp();
        $unixEpochTicks = '621355968000000000';

        // BCMath garante precisão mesmo em sistemas 32 bits
        return bcadd(bcmul((string)$seconds, '10000000'), $unixEpochTicks);
    }
}

?>
