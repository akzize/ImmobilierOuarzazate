<?php

use App\Rules\GeoJsonPolygon;
use Illuminate\Support\Facades\Validator;

test('a closed polygon drawn on the map passes', function () {
    $validator = Validator::make(['boundary' => squarePlot()], ['boundary' => [new GeoJsonPolygon]]);

    expect($validator->passes())->toBeTrue();
});

test('a missing outline passes', function () {
    $validator = Validator::make(['boundary' => null], ['boundary' => [new GeoJsonPolygon]]);

    expect($validator->passes())->toBeTrue();
});

test('shapes that are not a single closed polygon fail', function (mixed $boundary) {
    $validator = Validator::make(['boundary' => $boundary], ['boundary' => [new GeoJsonPolygon]]);

    expect($validator->errors()->first('boundary'))->toBe('The outline drawn on the map is not a valid shape.');
})->with([
    'not an array' => ['POLYGON((-6.94 30.93))'],
    'a point' => [['type' => 'Point', 'coordinates' => [-6.94, 30.93]]],
    'no coordinates' => [['type' => 'Polygon']],
    'a polygon with a hole' => [['type' => 'Polygon', 'coordinates' => [squarePlot()['coordinates'][0], squarePlot()['coordinates'][0]]]],
    'an open ring' => [['type' => 'Polygon', 'coordinates' => [[[-6.94, 30.93], [-6.93, 30.93], [-6.93, 30.94], [-6.94, 30.94]]]]],
    'fewer than three corners' => [['type' => 'Polygon', 'coordinates' => [[[-6.94, 30.93], [-6.93, 30.93], [-6.94, 30.93]]]]],
    'a latitude out of range' => [['type' => 'Polygon', 'coordinates' => [[[-6.94, 95.0], [-6.93, 30.93], [-6.93, 30.94], [-6.94, 95.0]]]]],
    'a coordinate given as text' => [['type' => 'Polygon', 'coordinates' => [[['-6.94', '30.93'], [-6.93, 30.93], [-6.93, 30.94], ['-6.94', '30.93']]]]],
    'too many positions' => [['type' => 'Polygon', 'coordinates' => [[
        ...array_map(fn (int $index): array => [-6.94 + $index / 100000, 30.93], range(0, GeoJsonPolygon::MAX_POSITIONS)),
        [-6.94, 30.93],
    ]]]],
]);
