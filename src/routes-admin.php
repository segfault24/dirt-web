<?php
use Dirt\Tools;

// //////////////////////////////////////////////
// // Admin Pages ////
// //////////////////////////////////////////////

$app->get('/admin', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isAdmin()) {
        $this->logger->warning('/admin unauthorized access attempt');
        return $response->withStatus(302)
            ->withHeader('Location', '/dashboard');
    }
    $u->setTemplateVars($args);

    $response = $this->cache->denyCache($response);
    return $this->renderer->render($response, 'admin/index.phtml', $args);
});

$app->get('/admin/create-user', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isAdmin()) {
        $this->logger->warning('/admin/create-user unauthorized access attempt');
        return $response->withStatus(302)
        ->withHeader('Location', '/dashboard');
    }
    $u->setTemplateVars($args);

    $response = $this->cache->denyCache($response);
    return $this->renderer->render($response, 'admin/create-user.phtml', $args);
});

$app->post('/admin/create-user', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isAdmin()) {
        $this->logger->warning('/admin/create-user unauthorized access attempt');
        return $response->withStatus(302)
        ->withHeader('Location', '/dashboard');
    }

    $usernm = $request->getParsedBody()['usernm'];
    $userpw = $request->getParsedBody()['userpw'];
    $userpwconf = $request->getParsedBody()['userpwconf'];
    $admin = false;

    $err = Dirt\Tools::createUser($usernm, $userpw, $userpwconf, $admin);
    if (empty($err)) {
        $this->logger->info('/admin/create-user created user ' . $usernm);
        $args['successmsg'] = "Successfully created user";
    } else {
        $this->logger->info('/admin/create-user failed to create user: ' . $err);
        $args['errormsg'] = $err;
    }
    $u->setTemplateVars($args);

    $response = $this->cache->denyCache($response);
    return $this->renderer->render($response, 'admin/create-user.phtml', $args);
});

$app->get('/admin/list-users', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isAdmin()) {
        $this->logger->warning('/admin/list-users unauthorized access attempt');
        return $response->withStatus(302)
        ->withHeader('Location', '/dashboard');
    }
    $u->setTemplateVars($args);

    $db = Dirt\Database::getDb();
    $sql = 'SELECT userId, username, name, dateCreated, lastLogin, admin, disabled FROM dirtuser';
    $stmt = $db->prepare($sql);
    $stmt->execute();

    $args['userlist'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = $this->cache->denyCache($response);
    return $this->renderer->render($response, 'admin/list-users.phtml', $args);
});

$app->get('/admin/structure-auths', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isAdmin()) {
        $this->logger->warning('/admin/structure-auths unauthorized access attempt');
        return $response->withStatus(302)
        ->withHeader('Location', '/dashboard');
    }
    $u->setTemplateVars($args);

    $db = Dirt\Database::getDb();
    $sql = 'SELECT r.regionName, s.structName, a.charName, u.username
            FROM dirtstructauth AS d
            JOIN structure AS s ON d.structId=s.structId
            JOIN region AS r ON s.regionId=r.regionId
            JOIN dirtapiauth AS a ON d.keyId=a.keyId
            JOIN dirtuser AS u ON u.userId=a.userId
            ORDER BY r.regionName, s.structName';
    $stmt = $db->prepare($sql);
    $stmt->execute();

    $args['authlist'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = $this->cache->denyCache($response);
    return $this->renderer->render($response, 'admin/structure-auths.phtml', $args);
});

$app->get('/admin/edit-doctrines', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isAdmin()) {
        $this->logger->warning('/admin/edit-doctrines unauthorized access attempt');
        return $response->withStatus(302)
        ->withHeader('Location', '/dashboard');
    }
    $u->setTemplateVars($args);

    $db = Dirt\Database::getDb();

    // list of doctrines for table
    $sql = 'SELECT d.doctrine, l.listId, l.name, s.structName, d.target
            FROM doctrine AS d
            JOIN dirtlist AS l ON d.listId=l.listId
            JOIN structure AS s ON d.locationId=s.structId';
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $args['doclist'] = $rows;

    // list of current user's lists
    $sql = 'SELECT listId, name FROM dirtlist WHERE userId=:userid ORDER BY name ASC';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':userid' => $u->getUserId()
    ));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $args['lists'] = $rows;

    // list of authed structures
    $sql = 'SELECT s.structId, s.structName
            FROM dirtstructauth AS dsa
            JOIN structure AS s ON dsa.structId=s.structId
            ORDER BY s.structName ASC';
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $args['structs'] = $rows;

    $response = $this->cache->denyCache($response);
    return $this->renderer->render($response, 'admin/edit-doctrines.phtml', $args);
});

$app->post('/admin/add-doctrine', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isAdmin()) {
        $this->logger->warning('/admin/add-doctrine unauthorized access attempt');
        return $response->withStatus(302)
        ->withHeader('Location', '/dashboard');
    }

    $listid = $request->getParsedBody()['doctrine-list'];
    $structid = $request->getParsedBody()['doctrine-struct'];
    $targetqt = $request->getParsedBody()['doctrine-targetqt'];

    $db = Dirt\Database::getDb();
    $sql = 'INSERT INTO doctrine (listId, locationId, quantity, target, lowestPrice)
            VALUES (:listid, :structid, 0, :targetqt, 0)';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listid' => $listid,
        ':structid' => $structid,
        ':targetqt' => $targetqt
    ));

    $this->logger->info('/admin/add-doctrine added doctrine for list ' . $listid . ' in struct ' . $structid);

    return $response->withStatus(302)->withHeader('Location', '/admin/edit-doctrines');
});

$app->post('/admin/delete-doctrine/{doctrineid}', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isAdmin()) {
        $this->logger->warning('/admin/delete-doctrine unauthorized access attempt');
        return $response->withStatus(302)
        ->withHeader('Location', '/dashboard');
    }

    $db = Dirt\Database::getDb();
    $sql = 'DELETE FROM doctrine WHERE doctrine=:doctrineid';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':doctrineid' => $args['doctrineid']
    ));

    $this->logger->info('/admin/delete-doctrine deleted doctrine ' . $args['doctrineid']);

    return $response->withStatus(302)->withHeader('Location', '/admin/edit-doctrines');
});

$app->get('/admin/set-stock-list', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isAdmin()) {
        $this->logger->warning('/admin/set-stock-list unauthorized access attempt');
        return $response->withStatus(302)
        ->withHeader('Location', '/dashboard');
    }
    $u->setTemplateVars($args);

    $db = Dirt\Database::getDb();

    // list of current user's lists
    $sql = 'SELECT listId, name FROM dirtlist WHERE userId=:userid ORDER BY name ASC';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':userid' => $u->getUserId()
    ));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $args['lists'] = $rows;

    $response = $this->cache->denyCache($response);
    return $this->renderer->render($response, 'admin/set-stock-list.phtml', $args);
});

$app->post('/admin/set-stock-list', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isAdmin()) {
        $this->logger->warning('/admin/set-stock-list unauthorized access attempt');
        return $response->withStatus(302)
        ->withHeader('Location', '/dashboard');
    }

    $listid = $request->getParsedBody()['item-list'];

    $db = Dirt\Database::getDb();
    $sql = 'INSERT INTO property (propertyName, propertyValue) VALUES ("stock-listid", :listid)
        ON DUPLICATE KEY UPDATE propertyValue=VALUES("propertyName")';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listid' => $listid
    ));

    $this->logger->info('/admin/set-stock-list set item stock list ' . $listid);

    return $response->withStatus(302)->withHeader('Location', '/admin');
});
