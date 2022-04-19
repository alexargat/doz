<?php

namespace App\Controller;

use App\Entity\Pharmacy;
use App\Form\PharmacyType;
use App\Repository\PharmacyRepository;
use App\Util\PharmacyHandler;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/pharmacy")
 */
class PharmacyController extends AbstractController
{
    private $pharmacyHandler;

    function __construct(PharmacyHandler $pharmacyHandler)
    {
        $this->pharmacyHandler = $pharmacyHandler;
    }

    /**
     * @Route("/", name="app_pharmacy_index", methods={"GET"})
     */
    public function index(PharmacyRepository $pharmacyRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $paginate = $paginator->paginate(
            $pharmacyRepository->findPublishedQueryBuilder($request->query->all())->getQuery(),
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 20)
        );

        $pages = $paginate->getItemNumberPerPage() > 0 ? ceil($paginate->getTotalItemCount() / $paginate->getItemNumberPerPage()) : 0;
        $page = $paginate->getCurrentPageNumber();
        $buttons = 6;

        if ($page <= $buttons) {
            $startPage = 1;
            $endPage = $buttons;
        } elseif ($page >= $pages - $buttons) {
            $startPage = $pages - $buttons;
            $endPage = $pages;
        } else {
            $startPage = $page - ceil($buttons / 2);
            $endPage = $page + ceil($buttons / 2);
        }

        $endPage = min($endPage, $pages);


        return $this->render('pharmacy/index.html.twig', [
            'paginate' => $paginate,
            'count' => $paginate->getItemNumberPerPage(),
            'total' => $paginate->getTotalItemCount(),
            'pages' => $pages,
            'page' => $paginate->getCurrentPageNumber(),
            'startPage' => $startPage,
            'endPage' => $endPage,
            'buttons' => $buttons
        ]);
    }

    /**
     * @Route("/upload", name="app_pharmacy_upload", methods={"GET", "POST"})
     */
    public function upload(Request $request): Response
    {

        $form = $this->createFormBuilder()
            ->add('file', FileType::class, [
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            'application/json',
                        ],
                    ])
                ]
            ])
            ->getForm()
            ->add('submit', SubmitType::class, ['attr' => ['class' => 'btn btn-warning']]);

        $form->handleRequest($request);

        if ($request->isMethod('POST')) {
            if ($form->isSubmitted() and $form->isValid()) {
                $file = $form->get('file')->getData();

                if ($data = json_decode(file_get_contents($file->getRealPath()), true)) {
                    $this->pharmacyHandler->upload($data);
                    return $this->redirect($this->generateUrl('app_pharmacy_preview'));
                } else {
                    $this->addFlash('danger', 'Unable to upload file!');
                }
            }
        }
        return $this->renderForm('pharmacy/upload.html.twig', ['form' => $form]);
    }

    /**
     * @Route("/preview", name="app_pharmacy_preview", methods={"GET", "POST"})
     */
    public function preview(Request $request): Response
    {
        $data = $this->pharmacyHandler->getUploaded();
        if ($request->isMethod('POST')) {
            $this->pharmacyHandler->import();
            $this->addFlash('success', 'Data has been imported successfully!');
            return $this->redirect($this->generateUrl('app_pharmacy_index'));
        }
        return $this->renderForm('pharmacy/preview.html.twig', ['data' => $data]);
    }

    /**
     * @Route("/download", name="app_pharmacy_download", methods={"GET"})
     */
    public function download(PharmacyRepository $pharmacyRepository, Request $request): Response
    {
        $data = $this->pharmacyHandler->export($request->query->all());

        $response = new Response();
        $filename = 'out.json';
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '";');
        $response->headers->set('Content-length', strlen($data));
        $response->sendHeaders();
        $response->setContent($data);

        return $response;
    }

    /**
     * @Route("/new", name="app_pharmacy_new", methods={"GET", "POST"})
     */
    public function new(Request $request, PharmacyRepository $pharmacyRepository): Response
    {
        $pharmacy = new Pharmacy();
        $form = $this->createForm(PharmacyType::class, $pharmacy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pharmacyRepository->add($pharmacy);
            return $this->redirectToRoute('app_pharmacy_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('pharmacy/new.html.twig', [
            'pharmacy' => $pharmacy,
            'form' => $form,
        ]);
    }


    /**
     * @Route("/{id}", name="app_pharmacy_show", methods={"GET"})
     */
    public function show(Pharmacy $pharmacy): Response
    {
        return $this->render('pharmacy/show.html.twig', [
            'pharmacy' => $pharmacy,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_pharmacy_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Pharmacy $pharmacy, PharmacyRepository $pharmacyRepository): Response
    {
        $form = $this->createForm(PharmacyType::class, $pharmacy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pharmacyRepository->add($pharmacy);
            return $this->redirectToRoute('app_pharmacy_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('pharmacy/edit.html.twig', [
            'pharmacy' => $pharmacy,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_pharmacy_delete", methods={"POST"})
     */
    public function delete(Request $request, Pharmacy $pharmacy, PharmacyRepository $pharmacyRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $pharmacy->getId(), $request->request->get('_token'))) {
            $pharmacyRepository->remove($pharmacy);
        }

        return $this->redirectToRoute('app_pharmacy_index', [], Response::HTTP_SEE_OTHER);
    }

}
