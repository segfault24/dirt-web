<?php

// //////////////////////////////////////////////
// // Contract Data ////
// //////////////////////////////////////////////

$GLOBALS['cap_ids'] = '671, 3514, 3764, 11567, 19720, 19722, 19724, 19726, 20183, 20185, 20187, 20189, 22852, 23757, 23773, 23911, 23913, 23915, 23917, 23919, 24483, 28352, 28844, 28846, 28848, 28850, 34328, 37604, 37605, 37606, 37607, 42124, 42125, 42126, 42241, 42242, 42243, 45645, 45647, 45649, 52907';

$app->get('/api/contract/public/exchange', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(401);
    }

    $db = Dirt\Database::getDb();
    $sql = 'SELECT co.`contractId`, locs.`locationName`, ich.`name` AS issuerName, co.`price`, co.`title`, co.`dateIssued`
            FROM publiccontract AS co
            LEFT JOIN dentity AS ich ON co.issuerId=ich.id
            LEFT JOIN dlocation AS locs ON co.`startLocationId`=locs.`locationId`
            WHERE co.`type`=2 AND co.`status`=1 AND `dateExpired`>NOW()
            ORDER BY co.dateIssued DESC';

    $stmt = $db->prepare($sql);
    $stmt->execute();

    return $response->withJson($stmt->fetchAll(PDO::FETCH_ASSOC));
});

$app->get('/api/contract/public/exchange/finished', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(401);
    }

    $db = Dirt\Database::getDb();
    $sql = 'SELECT co.`contractId`, locs.`locationName`, ich.`name` AS issuerName, ach.`name` AS acceptorName, co.`price`, co.`title`, co.`lastSeen`
            FROM publiccontract AS co
            LEFT JOIN dentity AS ich ON co.issuerId=ich.id
            LEFT JOIN dentity AS ach ON co.acceptorId=ach.id
            LEFT JOIN dlocation AS locs ON co.`startLocationId`=locs.`locationId`
            WHERE co.`type`=2 AND co.`status`=5
            ORDER BY co.lastSeen DESC LIMIT 1000';

    $stmt = $db->prepare($sql);
    $stmt->execute();

    return $response->withJson($stmt->fetchAll(PDO::FETCH_ASSOC));
});

$app->get('/api/contract/public/courier', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(401);
    }

    $db = Dirt\Database::getDb();
    $sql = 'SELECT co.`contractId`, ich.`name` AS issuerName, co.`dateIssued`, slocs.`locationName` AS startLocation, elocs.`locationName` AS endLocation, co.`collateral`, co.`volume`, co.`reward`, co.`daysToComplete`
            FROM publiccontract AS co
            LEFT JOIN dentity AS ich ON co.issuerId=ich.id
            LEFT JOIN dlocation AS slocs ON co.`startLocationId`=slocs.`locationId`
            LEFT JOIN dlocation AS elocs ON co.`endLocationId`=elocs.`locationId`
            WHERE co.`type`=4 AND co.`status`=1 AND `dateExpired`>NOW()
            ORDER BY co.dateIssued DESC';

    $stmt = $db->prepare($sql);
    $stmt->execute();

    return $response->withJson($stmt->fetchAll(PDO::FETCH_ASSOC));
});

$app->get('/api/contract/public/courier/finished', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(401);
    }

    $db = Dirt\Database::getDb();
    $sql = 'SELECT co.`contractId`, ich.`name` AS issuerName, co.`dateIssued`, slocs.`locationName` AS startLocation, elocs.`locationName` AS endLocation,
                co.`collateral`, co.`volume`, co.`reward`, ach.`name` AS acceptor, co.`lastSeen`
            FROM publiccontract AS co
            LEFT JOIN dentity AS ich ON co.issuerId=ich.id
            LEFT JOIN dentity AS ach ON co.acceptorId=ach.id
            LEFT JOIN dlocation AS slocs ON co.`startLocationId`=slocs.`locationId`
            LEFT JOIN dlocation AS elocs ON co.`endLocationId`=elocs.`locationId`
            WHERE co.`type`=4 AND co.`status`=5
            ORDER BY co.lastSeen DESC LIMIT 1000';

    $stmt = $db->prepare($sql);
    $stmt->execute();

    return $response->withJson($stmt->fetchAll(PDO::FETCH_ASSOC));
});

$app->get('/api/contract/public/capital', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(401);
    }

    $db = Dirt\Database::getDb();
    $sql = 'SELECT c.contractId, i.typeName, c.dateIssued, c.price, a.appraisal AS fittings, c.price - a.appraisal AS hullvalue, l.locationName, e.name AS issuer, c.title
            FROM publiccontract AS c
            JOIN publiccontractitem AS ci ON c.contractId=ci.contractId
            JOIN invtype AS i ON ci.typeId=i.typeId
            LEFT JOIN (
                SELECT c.contractId, SUM(j.best*ci.quantity) AS appraisal
                FROM publiccontract AS c
                JOIN publiccontractitem AS ci ON ci.contractId=c.contractId
                JOIN vjitabestsell AS j ON j.typeid=ci.typeid
                WHERE ci.typeId NOT IN ('.$GLOBALS['cap_ids'].')
                GROUP BY c.contractId
            ) AS a ON a.contractId=c.contractId
            LEFT JOIN dlocation AS l ON l.locationId=c.startLocationId
            LEFT JOIN dentity AS e ON e.id=c.issuerId
            WHERE ci.typeId IN ('.$GLOBALS['cap_ids'].')
            AND c.`type`=2 AND c.`status`=1 AND c.`dateExpired`>NOW()';

    $stmt = $db->prepare($sql);
    $stmt->execute();

    return $response->withJson($stmt->fetchAll(PDO::FETCH_ASSOC));
});

$app->get('/api/contract/public/capital/finished', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(401);
    }

    $db = Dirt\Database::getDb();
    $sql = 'SELECT c.contractId, i.typeName, c.lastSeen, c.price, a.appraisal AS fittings, c.price - a.appraisal AS hullvalue, l.locationName, e.name AS issuer, c.title
            FROM publiccontract AS c
            JOIN publiccontractitem AS ci ON c.contractId=ci.contractId
            JOIN invtype AS i ON ci.typeId=i.typeId
            LEFT JOIN (
                SELECT c.contractId, SUM(j.best*ci.quantity) AS appraisal
                FROM publiccontract AS c
                JOIN publiccontractitem AS ci ON ci.contractId=c.contractId
                JOIN vjitabestsell AS j ON j.typeid=ci.typeid
                WHERE ci.typeId NOT IN ('.$GLOBALS['cap_ids'].')
                GROUP BY c.contractId
            ) AS a ON a.contractId=c.contractId
            LEFT JOIN dlocation AS l ON l.locationId=c.startLocationId
            LEFT JOIN dentity AS e ON e.id=c.issuerId
            WHERE ci.typeId IN ('.$GLOBALS['cap_ids'].')
            AND c.`type`=2 AND c.`status`=5
            ORDER BY c.lastSeen DESC LIMIT 1000';

    $stmt = $db->prepare($sql);
    $stmt->execute();

    return $response->withJson($stmt->fetchAll(PDO::FETCH_ASSOC));
});

