<?php

// //////////////////////////////////////////////
// // Static Data ////
// //////////////////////////////////////////////

$app->get('/api/search-types', function ($request, $response, $args) {
    $db = Dirt\Database::getDb();

    $sql = 'SELECT typeId AS value, typeName AS label FROM invtype WHERE published=1 ORDER BY typeName;';
    $stmt = $db->prepare($sql);
    $stmt->execute();

    $response = $this->cache->withExpires($response, time() + 86400);
    return $response->withJson($stmt->fetchAll(PDO::FETCH_ASSOC));
});

$app->get('/api/market-groups', function ($request, $response, $args) {
    $db = Dirt\Database::getDb();

    $sql = 'SELECT marketGroupId, marketGroupName, parentGroupId, hasTypes FROM marketgroup ORDER BY marketGroupName;';
    $stmt = $db->prepare($sql);
    $stmt->execute();

    $response = $this->cache->withExpires($response, time() + 86400);
    return $response->withJson($stmt->fetchAll(PDO::FETCH_ASSOC));
});

$app->get('/api/market-types', function ($request, $response, $args) {
    $db = Dirt\Database::getDb();

    $sql = 'SELECT typeId, typeName, marketGroupId FROM invtype WHERE published=1 AND marketGroupId IS NOT NULL ORDER BY typeName;';
    $stmt = $db->prepare($sql);
    $stmt->execute();

    $response = $this->cache->withExpires($response, time() + 86400);
    return $response->withJson($stmt->fetchAll(PDO::FETCH_ASSOC));
});

$app->get('/api/types/{typeid}', function ($request, $response, $args) {
    $db = Dirt\Database::getDb();

    $sql = 'SELECT typeId, typeName, volume, marketGroupId FROM invtype WHERE typeId=:typeid;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':typeid' => $args['typeid']
    ));

    $response = $this->cache->withExpires($response, time() + 86400);
    return $response->withJson($stmt->fetch(PDO::FETCH_ASSOC));
});

$app->get('/api/market-group/{group}', function ($request, $response, $args) {
    $db = Dirt\Database::getDb();

    $sql = 'SELECT marketGroupId, marketGroupName FROM marketgroup WHERE marketGroupId=:group;';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':group', $args['group']);
    $stmt->execute();

    $output['groups'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = 'SELECT typeId, typeName FROM invtype WHERE marketGroupId=:group;';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':group', $args['group']);
    $stmt->execute();

    $output['types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = $this->cache->withExpires($response, time() + 86400);
    return $response->withJson($output);
});

