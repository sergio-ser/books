<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Validation;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApiController extends AbstractFOSRestController
{
    protected LoggerInterface $logger;
    protected ManagerRegistry $doctrine;
    protected $validator;

    public function __construct(LoggerInterface $logger, ManagerRegistry $doctrine)
    {
        $this->logger = $logger;
        $this->doctrine = $doctrine;
        $this->validator = Validation::createValidator();
    }


    protected function setListingConfigurations(Request $request, &$page, &$noRecords, &$sortField, &$sortType): void
    {
        $page = (int)$request->get('offset') ? (int)$request->get('offset') - 1 : 0;

        $noRecords = match ($request->get('limit')) {
            -1 => PHP_INT_MAX,
            null => 20,
            default => (int)$request->get('limit'),
        };

        $sort = $request->get('sort');
        $sortFields = explode('-', $sort);

        $sortField = $sortFields[1] ?? ($sortFields[0] ?: 'id');
        $sortType = isset($sortFields[1]) ? 'DESC' : ($sortFields[0] ? 'ASC' : 'DESC');
    }

    protected function setHeaderLink(Request $request, $page, $noRecords, $noTotal, $params = []): string
    {

        // get current url
        $url = $this->generateUrl($request->get('_route'), $params, UrlGeneratorInterface::ABSOLUTE_URL);

        // get last offset
        $lastOffset = ceil($noTotal / $noRecords);

        // first, last, prev and next link
        $firstLink = $url . '?offset=1&limit=' . $noRecords;
        $lastLink = $url . '?offset=' . $lastOffset . '&limit=' . $noRecords;
        $prevLink = $nextLink = null;

        if ($page + 2 <= $lastOffset) {
            $nextLink = $url . '?offset=' . ($page + 2) . '&limit=' . $noRecords;
        }
        if ($page >= 1) {
            $prevLink = $url . '?offset=' . $page . '&limit=' . $noRecords;
        }

        // header link
        $headerLink = '<' . $firstLink . '>; rel="first", <' . $lastLink . '>; rel="last"';
        if ($prevLink) {
            $headerLink .= ', <' . $prevLink . '>; rel="prev"';
        }
        if ($nextLink) {
            $headerLink .= ', <' . $nextLink . '>; rel="next"';
        }

        return $headerLink;
    }
}