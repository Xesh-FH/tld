<?php

namespace App\Controller;

use App\Entity\GitCommit;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GitCommitsController extends AbstractController
{
    private SerializerInterface $serializerInterface;
    private Serializer $serializer;

    public function __construct(SerializerInterface $serializerInterface, NormalizerInterface $normalizer)
    {
        $this->serializerInterface = $serializerInterface;
        $this->serializer = new Serializer([$normalizer]);
    }

    #[Route('/{user}/{repository}/{since?-4 weeks}/{until?today}', name: 'app_git_commits')]
    public function fetchCommits(
        string $user,
        string $repository,
        string $since,
        string $until,
        HttpClientInterface $client
    ): Response
    {
        $since = new DateTime($since);
        $until = new DateTime($until);

        if($until < $since) {
            return new JsonResponse(
                [
                    "erreur" => "La date de fin ne peut pas précéder la date de début"
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $url =
            $this->getParameter("Github_API").
            "repos/".
            $user.
            "/".
            $repository.
            "/commits?since=".
            $since->format('Y-m-d') .
            "&until=".
            $until->format('Y-m-d')
        ;

        $githubResponse = $client->request(
            'GET',
            $url,
        );

        $statusCode = $githubResponse->getStatusCode();
        $content = $githubResponse->getContent();
        $content = $githubResponse->toArray();
        /** @var GitCommit[] */
        $commits = [];

        foreach ($content as $data) {
            $commit = $this->serializer->denormalize($data['commit'], GitCommit::class);
            $commit->setDate($commit->getCommitter()->getDate());
            array_push($commits, $commit);
        }

        $responseArray = [
            "status" => Response::HTTP_NO_CONTENT,
            "message" => "Aucun commit trouvé dans cette période",
        ];

        switch($statusCode) {
            case Response::HTTP_OK:
                if ($commits) {
                    $responseArray = [
                        "year" => $commits[0]->getDate()->format('Y'),
                        "week" => $commits[0]->getDate()->format('W'),
                        "count" => count($commits),
                        "commits" => $commits,
                    ];
                }
                break;

            case Response::HTTP_NOT_FOUND:
                $responseArray = [
                    "status" => $statusCode,
                    "message" => "user et/ou repository non trouvé(s)",
                ];
                break;

            case Response::HTTP_INTERNAL_SERVER_ERROR:
                $responseArray = [
                    "status" => $statusCode,
                    "message" => "Une erreur est survenue sur le serveur de GitHub",
                ];
                break;
        }

        $response = $this->serializerInterface->serialize($responseArray, 'json');
        return new JsonResponse($response, Response::HTTP_OK, [], true);
    }
}
