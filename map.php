<?php
        $points  = \Entity\Point::all();
        if (isset($points)) {
            if (!is_array($points)){
                $points = $points->toArray();
            }
                $newPoints = [];
                foreach ($points as $k => $point) {
                    if (!empty($point) && $point['brand_id'] != 0 ) {
                        $newPoints[$k] = $point;
                        $phones = \Entity\Phone::where('object_id', '=', $point['id'])->get();
                        $phones = $phones->toArray();
                        foreach ($phones as $i => $ph) {
                            if ($point['id'] == $ph['object_id']) {
                                $phn[$k]['phones'][$i]['phoneTitle'] = $ph['title'];
                                $phn[$k]['phones'][$i]['phoneNumber'] = $ph['number'];
                                $phn[$k]['phones'][$i]['phoneType'] = $ph['type_id'];
                                $phn[$k]['phones'][$i]['phoneId'] = $ph['id'];
                                $phn[$k]['phones'][$i]['object_type'] = $ph['object_type'];
                            }
                        }
                        if (isset($phn[$k])) {
                            $newPoints[$k] = array_merge($newPoints[$k], $phn[$k]);
                        }
                    }
                }

            $fileGeoCode = 'pointsWithGeocode.txt';
            $dateNow = strtotime('-7 day');

                if (!file_exists($fileGeoCode) || date('Y-m-d H:m:s', filemtime($fileGeoCode) ) < date('Y-m-d H:m:s', $dateNow) ){
                if (isset($newPoints)){
                    foreach ($newPoints as $k => $point){
                        if (!empty($point)){
                            $address = $point['city'] .','.  $point['street'] . ','.  $point['building'];
                            //лимит запросов к HTTP Геокодеру составляет 1 000 запросов в сутки
//                            $apiKey = '';
                            $apiKey = '';
                            if ( ! $geocode = @file_get_contents( 'http://geocode-maps.yandex.ru/1.x/?apikey='.$apiKey.'&geocode=' . urlencode($address))) {
                                $error = error_get_last();
                                throw new Exception( 'HTTP request failed. Error: ' . $error['message'] );
                            }

                            $xml = new SimpleXMLElement( $geocode );
                            $xml->registerXPathNamespace( 'ymaps', 'http://maps.yandex.ru/ymaps/1.x' );
                            $xml->registerXPathNamespace( 'gml', 'http://www.opengis.net/gml' );

                            $result = $xml->xpath( '/ymaps:ymaps/ymaps:GeoObjectCollection/gml:featureMember/ymaps:GeoObject/gml:Point/gml:pos' );

                            if ( isset( $result[0] )) {
                                list( $longitude, $latitude ) = explode( ' ', $result[0] );
                                $coords = array($latitude, $longitude);
                                $newPoints[$k] = array_merge($newPoints[$k], array('coords' => $coords ));
                                unset($result);
                            }
                        }else{
                            unset($newPoints[$k]);
                        }
                    }
                }

                $newPoints =  array_values($newPoints);
//                $data = serialize($newPoints);      // PHP формат сохраняемого значения.
                $data = json_encode($newPoints, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);  // JSON формат сохраняемого значения.
                file_put_contents($fileGeoCode, $data);
                $bookshelf = file_get_contents($fileGeoCode);
//                $bookshelf = json_decode($data, TRUE); // Если нет TRUE то получает объект, а не массив.
//                $bookshelf = unserialize($data);
            }else{
//                $bookshelf = unserialize(file_get_contents('pointsWithGeocode.txt'));
                $bookshelf = file_get_contents('pointsWithGeocode.txt');
                $newPoint = json_decode(file_get_contents('pointsWithGeocode.txt'), TRUE);
            }
 }
?>
