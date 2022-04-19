<?php
/**
 * Created by PhpStorm.
 * User: oleksandrarhat
 * Date: 4/19/22
 * Time: 11:27 AM
 */

namespace App\Util;


use App\Entity\Pharmacy;
use App\Repository\PharmacyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PharmacyHandler
{
    private $em;
    private $requestStack;

    function __construct(EntityManagerInterface $em, RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
        $this->em = $em;
    }

    /**
     * @return array
     */
    function getUploaded(){
        $session = $this->requestStack->getSession();
        $data = $session->get('data');
        return $data;
    }

    /**
     * @param $fileName string
     * @return bool
     */
    function upload($data){
        $session = $this->requestStack->getSession();
        $session->set('data', $data);
        return true;
    }


    /**
     *
     */
    function import(){

        $data = $this->getUploaded();
        $alreadyExists = [];
        /** @var Pharmacy $entity */
        foreach ($this->em
                ->getRepository(Pharmacy::class)
                ->findBy(
                    [
                        'name' => array_column($data, 'nazwa'),
                        'town' => array_column($data, 'miejscowosc')
                    ]
                ) as $entity){
            $alreadyExists[$entity->getName().'<=>'.$entity->getTown()] = $entity;
        }

        foreach ($data as $row){
            if($name = $row['nazwa'] ?? null and $town = $row['miejscowosc'] ?? null){
                if(! $entity = $alreadyExists[$name.'<=>'.$town] ?? null){
                    $entity = new Pharmacy();
                    $entity->setName($name);
                    $entity->setTown($town);
                    $this->em->persist($entity);
                }
                $entity->setPostIndex($row['kod_pocztowy'] ?? null);
                $entity->setStreet($row['ulica'] ?? null);
                $entity->setLongitude($row['gps_dlugosc'] ?? null );
                $entity->setLatitude($row['gps_szerokosc'] ?? null );
            }
        }

        $this->em->flush();
    }

    function export($query){
        /** @var PharmacyRepository $repo */
        $repo = $this->em->getRepository(Pharmacy::class);
        $data = $repo->findPublishedQueryBuilder($query)->getQuery()->getArrayResult();
        return json_encode($data);
    }


}