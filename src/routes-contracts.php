<?php
use Dirt\Tools;

// //////////////////////////////////////////////
// // Contract Pages ////
// //////////////////////////////////////////////

$app->get('/contracts', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
            ->withHeader('Location', '/login');
    }
    $u->setTemplateVars($args);

    return $this->renderer->render($response, 'contracts.phtml', $args);
});

$app->get('/public-contracts', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
            ->withHeader('Location', '/login');
    }
    $u->setTemplateVars($args);

    return $this->renderer->render($response, 'public-contracts.phtml', $args);
});

$app->get('/contract/{contractid}', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
            ->withHeader('Location', '/login');
    }
    $u->setTemplateVars($args);

    $db = Dirt\Database::getDb();
    // try the personal contract table for exchange
    $sql = 'SELECT ie.name AS issuer, sl.locationName, ae.name AS assignee, ac.name AS acceptor, c.price, cs.value AS status, ca.value AS availability, c.title, c.dateIssued, c.dateCompleted
            FROM contract AS c
            JOIN contractstatus AS cs ON cs.id=c.status
            JOIN contractavailability AS ca ON ca.id=c.availability
            LEFT JOIN dlocation AS sl ON sl.locationId=c.startLocationId
            LEFT JOIN dentity AS ie ON ie.id=c.issuerId
            LEFT JOIN dentity AS ae ON ae.id=c.assigneeId
            LEFT JOIN dentity AS ac ON ac.id=c.acceptorId
            WHERE c.contractId=:contractid';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(':contractid' => $args['contractid']));
    if ($stmt->rowCount() > 0) {
        // pass info to the template
        $args['cinfo'] = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
        // get the items
        $sql = 'SELECT i.typeId, t.typeName, i.quantity FROM contractitem AS i
                JOIN invtype AS t ON t.typeId=i.typeId
                WHERE contractId=:contractid AND i.included=1';
        $stmt = $db->prepare($sql);
        $stmt->execute(array(':contractid' => $args['contractid']));
        if ($stmt->rowCount() > 0) {
            $args['offeritems'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $sql = 'SELECT i.typeId, t.typeName, i.quantity FROM contractitem AS i
                JOIN invtype AS t ON t.typeId=i.typeId
                WHERE contractId=:contractid AND i.included=0';
        $stmt = $db->prepare($sql);
        $stmt->execute(array(':contractid' => $args['contractid']));
        if ($stmt->rowCount() > 0) {
            $args['askitems'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $response = $this->cache->withExpires($response, time() + 300);
        return $this->renderer->render($response, 'contract.phtml', $args);
    }

    // else try the corp contract table for exchange
    $sql = 'SELECT ie.name AS issuer, sl.locationName, ae.name AS assignee, ac.name AS acceptor, c.price, cs.value AS status, ca.value AS availability, c.title, c.dateIssued, c.dateCompleted
            FROM corpcontract AS c
            JOIN contractstatus AS cs ON cs.id=c.status
            JOIN contractavailability AS ca ON ca.id=c.availability
            LEFT JOIN dlocation AS sl ON sl.locationId=c.startLocationId
            LEFT JOIN dentity AS ie ON ie.id=c.issuerId
            LEFT JOIN dentity AS ae ON ae.id=c.assigneeId
            LEFT JOIN dentity AS ac ON ac.id=c.acceptorId
            WHERE c.contractId=:contractid';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(':contractid' => $args['contractid']));
    if ($stmt->rowCount() > 0) {
        // pass info to the template
        $args['cinfo'] = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
        // get the items
        $sql = 'SELECT i.typeId, t.typeName, i.quantity FROM corpcontractitem AS i
                JOIN invtype AS t ON t.typeId=i.typeId
                WHERE contractId=:contractid AND i.included=1';
        $stmt = $db->prepare($sql);
        $stmt->execute(array(':contractid' => $args['contractid']));
        if ($stmt->rowCount() > 0) {
            $args['offeritems'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $sql = 'SELECT i.typeId, t.typeName, i.quantity FROM corpcontractitem AS i
                JOIN invtype AS t ON t.typeId=i.typeId
                WHERE contractId=:contractid AND i.included=0';
        $stmt = $db->prepare($sql);
        $stmt->execute(array(':contractid' => $args['contractid']));
        if ($stmt->rowCount() > 0) {
            $args['askitems'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $response = $this->cache->withExpires($response, time() + 300);
        return $this->renderer->render($response, 'contract.phtml', $args);
    }

    // else try the public contract table for exchange
    $sql = 'SELECT ie.name AS issuer, sl.locationName, c.price, cs.value AS status, c.title, c.dateIssued, c.lastSeen
            FROM publiccontract AS c
            JOIN contractstatus AS cs ON cs.id=c.status
            LEFT JOIN dlocation AS sl ON sl.locationId=c.startLocationId
            LEFT JOIN dentity AS ie ON ie.id=c.issuerId
            WHERE c.contractId=:contractid';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(':contractid' => $args['contractid']));
    if ($stmt->rowCount() > 0) {
        // pass info to the template
        $args['cinfo'] = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
		$args['cinfo']['availability'] = "Public";
        // get the items
        $sql = 'SELECT i.typeId, t.typeName, i.quantity FROM publiccontractitem AS i
                JOIN invtype AS t ON t.typeId=i.typeId
                WHERE contractId=:contractid AND i.included=1';
        $stmt = $db->prepare($sql);
        $stmt->execute(array(':contractid' => $args['contractid']));
        if ($stmt->rowCount() > 0) {
            $args['offeritems'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $sql = 'SELECT i.typeId, t.typeName, i.quantity FROM publiccontractitem AS i
                JOIN invtype AS t ON t.typeId=i.typeId
                WHERE contractId=:contractid AND i.included=0';
        $stmt = $db->prepare($sql);
        $stmt->execute(array(':contractid' => $args['contractid']));
        if ($stmt->rowCount() > 0) {
            $args['askitems'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $response = $this->cache->withExpires($response, time() + 300);
        return $this->renderer->render($response, 'contract.phtml', $args);
    }

    // else fail
    return $this->renderer->render($response, 'contract.phtml', $args);
});

$app->get('/doctrines', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
        ->withHeader('Location', '/login');
    }
    $u->setTemplateVars($args);

    $db = Dirt\Database::getDb();
    $sql = 'SELECT l.listId, l.name, s.structName, d.quantity, d.target, d.lowestPrice
            FROM doctrine AS d
            JOIN dirtlist AS l ON d.listId=l.listId
            JOIN structure AS s ON d.locationId=s.structId';
    $stmt = $db->prepare($sql);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $args['doclist'] = $rows;

    $response = $this->cache->withExpires($response, time() + 120);
    return $this->renderer->render($response, 'doctrines.phtml', $args);
});

$app->get('/item-stock', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
        ->withHeader('Location', '/login');
    }
    $u->setTemplateVars($args);

    $db = Dirt\Database::getDb();
    $sql = 'select
                tgt.typeid,
                t.typename,
                coalesce(available, 0) as available,
                target,
                greatest(target - coalesce(available, 0), 0) as need,
                100 * coalesce(available, 0) / target as goal,
                j.best as jitasell,
                coalesce(stagingsell, 0) as stagingsell,
                100 * coalesce((stagingsell - j.best - 0.01 * j.best - 800 * t.volume) / j.best, 0) as margin,
                1.1 * (j.best + 0.01 * j.best + 800 * t.volume + 0.05 * j.best) as targetprice,
                coalesce(mktcap, 0) as mktcap,
                j.best * target as tgtcap
            from vjitabestsell j
            join invtype t on t.typeid=j.typeid
            cross join (
                select typeid, sum(quantity) as target
                from dirtlistitem
                where listid=(select propertyValue from property where propertyname="stocklistid")
                group by typeId
            ) tgt on tgt.typeid=j.typeid
            left join (
                select
                    o.typeid,
                    min(o.price) as stagingsell,
                    sum(o.volumeRemain) as available,
                    sum(o.price * o.volumeRemain) as mktcap
                from marketorder o
                left join vjitabestsell j on j.typeId=o.typeId
                left join orderset s on s.setId=o.setId
                left join invtype t on t.typeid=o.typeid
                where s.setId in (select setId from latestset)
                and o.locationId in (1038708751029)
                and o.isBuyOrder=false
                and o.price <= 2 * (j.best + 0.01 * j.best + 800 * t.volume)
                group by o.typeId
            ) stg on stg.typeid=j.typeid';
    $stmt = $db->prepare($sql);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $args['stocklist'] = $rows;

    $response = $this->cache->withExpires($response, time() + 120);
    return $this->renderer->render($response, 'item-stock.phtml', $args);
});
