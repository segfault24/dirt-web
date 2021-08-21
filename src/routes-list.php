<?php
use Dirt\Tools;

// get user lists
$app->get('/my-lists', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
            ->withHeader('Location', '/login');
    }
    $u->setTemplateVars($args);

    // retrieve all the user's lists
    $db = Dirt\Database::getDb();
    $sql = 'SELECT dl.listId, dl.userId, dl.name, dl.public
            FROM dirtlist AS dl
            WHERE userId=:userid
            GROUP BY dl.listId;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':userid' => $u->getUserId()
    ));

    $lists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $args['data'] = $lists;

    return $this->renderer->render($response, 'my-lists.phtml', $args);
});

// create a new list
$app->post('/my-lists', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
            ->withHeader('Location', '/login');
    }

    // set default parameters if necessary
    $listname = $request->getParsedBody()['list-add-name'];
    if ($listname == '') {
        $listname = '_list_' . uniqid();
    }
    //$public = $request->getParsedBody()['info']['public'];
    //if ($public == '') {
        $public = 0;
    //}

    // create the new list
    $db = Dirt\Database::getDb();
    $sql = 'INSERT INTO dirtlist (name, userId, public) VALUES (:listname, :userid, :public);';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listname' => $listname,
        ':userid' => $u->getUserId(),
        ':public' => $public
    ));

    return $response->withStatus(302)->withHeader('Location', '/my-lists');
});

// get specific user list
$app->get('/list/{listid}', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
            ->withHeader('Location', '/login');
    }
    $u->setTemplateVars($args);

    // retrieve the list's info
    $db = Dirt\Database::getDb();
    $sql = 'SELECT listId, userId, name, public FROM dirtlist WHERE listId=:listid;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listid' => $args['listid']
    ));

    // 404 not found if the list doesn't exist
    if ($stmt->rowCount() == 0) {
        return $response->withStatus(404);
    }

    $listinfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // 403 forbidden if the user doesn't own the list and it's not public
    if ($listinfo['public'] != 1 && $listinfo['userId'] != $u->getUserId()) {
        return $response->withStatus(403);
    }

    // retrieve the items in the list
    $sql = 'SELECT li.typeId, i.typeName, li.quantity FROM dirtlistitem AS li JOIN invtype AS i ON li.typeId=i.typeId WHERE li.listId=:listid;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listid' => $args['listid']
    ));

    $listitems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $args['listinfo'] = $listinfo;
    $args['listitems'] = $listitems;
    return $this->renderer->render($response, 'list.phtml', $args);
});

// delete specific user list
$app->post('/list/{listid}/delete-list', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
            ->withHeader('Location', '/login');
    }
    $u->setTemplateVars($args);

    // retrieve the list's info
    $db = Dirt\Database::getDb();
    $sql = 'SELECT listId, userId FROM dirtlist WHERE listId=:listid;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listid' => $args['listid']
    ));

    // 404 not found if the list doesn't exist
    if ($stmt->rowCount() == 0) {
        return $response->withStatus(404);
    }

    $listinfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // 403 forbidden if the user doesn't own the list
    if ($listinfo['userId'] != $u->getUserId()) {
        return $response->withStatus(403);
    }

    // remove listitems
    $sql = 'DELETE FROM dirtlistitem WHERE listId=:listid;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listid' => $args['listid']
    ));

    // remove list
    $sql = 'DELETE FROM dirtlist WHERE listId=:listid;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listid' => $args['listid']
    ));

    return $response->withStatus(302)->withHeader('Location', '/my-lists');
});

// delete specific item on user list
$app->post('/list/{listid}/delete-item/{typeid}', function ($request, $response, $args) {
$u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
            ->withHeader('Location', '/login');
    }

    // retrieve the list's info
    $db = Dirt\Database::getDb();
    $sql = 'SELECT listId, userId, public FROM dirtlist WHERE listId=:listid;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listid' => $args['listid']
    ));

    // 404 not found if the list doesn't exist
    if ($stmt->rowCount() == 0) {
        return $response->withStatus(404);
    }

    $listinfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // 403 forbidden if the user doesn't own the list
    if ($listinfo['userId'] != $u->getUserId()) {
        return $response->withStatus(403);
    }

    // retrieve the item from the list
    $sql = 'SELECT typeId FROM dirtlistitem WHERE listId=:listid AND typeId=:typeid;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listid' => $args['listid'],
        ':typeid' => $args['typeid']
    ));

    // 404 not found if the item isn't in the list
    if ($stmt->rowCount() == 0) {
        return $response->withStatus(404);
    }

    // remove the listitem
    $sql = 'DELETE FROM dirtlistitem WHERE listId=:listid AND typeId=:typeid;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listid' => $args['listid'],
        ':typeid' => $args['typeid']
    ));

    return $response->withStatus(302)->withHeader('Location', '/list/'.$args['listid']);
});

