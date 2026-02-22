<?php

namespace App\Controllers;

use Core\Http\Message\Request;
use Core\Http\Message\Response;
use Core\View\Internal\View;

class UserController extends Controller
{
    public function __construct(private View $view)
    {
    }

    public function index(): string
    {
        return $this->view->render('users.index');
    }

    public function show(Request $request): Response
    {
        $id = $request->getAttribute('id');

        return $this->json(['id' => $id, 'name' => 'User ' . $id]);
    }
}
