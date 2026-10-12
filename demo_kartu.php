<?php

include_once("demo/kartu.php");

$kartu = [
    new SilverCard("4111-1111", 500000, 123),
    new GoldCard("4222-2222", 2000000, 456, 90001, 750000),
];

foreach ($kartu as $k) {

    echo "<b>" . get_class($k) . "</b><br>";

    $k->charge(
        200000,
        $k instanceof GoldCard ? 456 : 123
    );

    if ($k instanceof TapPayment) {
        $k->pay(50000);
    }

    if ($k instanceof EWallet) {
        $k->topUp(25000);
        $k->useEWallet(30000);
    }

    echo "<br>";
}