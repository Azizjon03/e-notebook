<?php

use App\Modules\Geo\Region;

it('measures Tashkent to Samarkand at about 270 km', function () {
    $km = Region::distanceKm(41.2995, 69.2401, 39.6542, 66.9597);

    expect($km)->toBeGreaterThan(260)->toBeLessThan(280);
});
