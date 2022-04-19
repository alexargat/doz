<?php
/**
 * Created by PhpStorm.
 * User: oleksandrarhat
 * Date: 4/6/22
 * Time: 5:52 PM
 */

namespace App\Controller;


use App\Util\ApiHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{

    function __construct()
    {
    }

    /**
     * @Route("/default", name="default")
     */
    public function index()
    {
        return $this->render('index.html.twig');
    }

}