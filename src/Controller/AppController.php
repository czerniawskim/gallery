<?php

namespace App\Controller;

use App\Form\ImgLoadType;
use App\Repository\CategoriesRepository;
use App\Repository\GalleryRepository;
use App\Repository\ReasonsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class AppController extends AbstractController
{
    public function __construct(GalleryRepository $gr, EntityManagerInterface $em, ReasonsRepository $rr)
    {
        $this->gr = $gr;
        $this->em = $em;
        $this->rr = $rr;
    }

    /**
     * @Route("/", name="homepage", methods={"GET"})
     */
    public function index(PaginatorInterface $paginator, Request $request, SessionInterface $session)
    {
        if (!$session->get('repReasons')) $session->set('repReasons', $this->rr->findAll());

        return $this->render('app/homepage.html.twig', [
            'gallery' => $paginator->paginate($this->gr->findBy([], ['addedAt' => 'DESC'], 105), $request->query->getInt('page', 1), 15)
        ]);
    }

    /**
     * @Route("/explore/{attr}", name="explore", methods={"GET"}, defaults={"attr": ""})
     */
    public function explore(string $attr, CategoriesRepository $cr, PaginatorInterface $paginator, Request $request)
    {
        if ($attr == "")
            return $this->render('app/explore/categories.html.twig', [
                'categories' => $cr->findBy([], ['Name' => 'ASC'])
            ]);
        else {
            return $this->render('app/explore/explore.html.twig', [
                'results' => $paginator->paginate($attr == "all" ? $this->gr->findAll() : $this->gr->getMatchingResults($attr), $request->query->getInt('page', 1), 20)
            ]);
        }
    }

    /**
     * @Route("/check-view/{id}", methods={"POST"})
     */
    public function checkView(int $id, bool $viewed = false)
    {
        $post = $this->gr->findOneBy(['id' => $id]);
        if ($post) {
            foreach ($post->getViews() as $v) {
                if ($v->getId() == $this->getUser()->getId()) {
                    $viewed = true;
                    break;
                }
            }
            if (!$viewed) {
                $post->addView($this->getUser());
                $this->em->flush();
                return new Response("Successfully added to viewed", 200);
            } else {
                return new Response("Seen already", 202);
            }
        } else {
            return new Response("There is no such object matching requirements.", 404);
        }
    }

    /**
     * @Route("/like/{id}", name="like", methods={"GET"})
     */
    public function like(int $id, bool $liked = false, Request $request)
    {
        $post = $this->gr->findOneBy(['id' => $id]);
        if ($post) {
            foreach ($post->getLikes() as $l) {
                if ($l->getId() == $this->getUser()->getId()) {
                    $liked = true;
                    break;
                }
            }

            !$liked ? $post->addLike($this->getUser()) : $post->removeLike($this->getUser());
            $this->em->flush();
            return $this->redirect($request->query->get('ref'));
        } else {
            throw new \Exception("No object found. Refresh page and try again", 404);
        }
    }

    /**
     * @Route("/upload-new", name="upload", methods={"GET", "POST"})
     */
    public function upload(Request $request, string $error = null)
    {
        $upload = $this->createForm(ImgLoadType::class);
        $upload->handleRequest($request);

        if ($upload->isSubmitted() && $upload->isValid()) {
            $data = $upload->getData();

            try {
                $new = new \App\Entity\Gallery;
                $name = ($this->gr->getLastId() + 1) . "." . $data['image']->guessExtension();

                $data['image']->move(
                    'gallery/',
                    $name
                );

                $new->setImage($name);
                $new->setCategory($data['category']);
                $new->setUploader($this->getUser());
                $new->setAddedAt(new \DateTime());

                $this->em->persist($new);
                $this->em->flush();

                return $this->redirectToRoute('homepage', []);
            } catch (FileException $e) {
                $error = "Something went wrong. Try again. Error message: {$e->getMessage()}";
            }
        }

        return $this->render('app/upload.html.twig', [
            'upload' => $upload->createView(),
            'error' => $error
        ]);
    }

    /**
     * @Route("/report", name="report", methods={"POST"})
     */
    public function report(Request $request)
    {
        $data = json_decode($request->request->get('data'), TRUE);

        if ($data['id'] && $data['reasonId']) {
            $reason = $this->rr->findOneBy(['id' => $data['reasonId']]);
            $img = $this->gr->findOneBy(['id' => $data['id']]);

            if ($reason && $img) {
                try {
                    $report = new \App\Entity\Reports;
                    $report->setUser($this->getUser());
                    $report->setNote($data['note']);
                    $report->setReason($reason);
                    $report->setStatus("Reported");
                    $report->setItem($img);

                    $this->em->persist($report);
                    $this->em->flush();

                    return new Response("Success", 200);
                } catch (QueryException $e) {
                    return new Response("Execution problem. Error: {$e->getMessage()}", 500);
                }
            } else {
                return new Response('Reason or image not found', 404);
            }
        } else return new Response('Data are missing', 400);
    }
}
