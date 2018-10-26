<?php
/**
 * Created by PhpStorm.
 * User: bdunogier
 * Date: 23/09/2018
 * Time: 23:24
 */

namespace BD\EzPlatformGraphQLBundle\DomainContent\SchemaWorker\ContentType;

use BD\EzPlatformGraphQLBundle\DomainContent\SchemaWorker\BaseWorker;
use BD\EzPlatformGraphQLBundle\DomainContent\SchemaWorker\SchemaWorker;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Values\ContentType\ContentTypeGroup;

class AddDomainContentToDomainGroup extends BaseWorker implements SchemaWorker
{
    public function work(array &$schema, array $args)
    {
        $contentType = $args['ContentType'];
        $contentTypeGroup = $args['ContentTypeGroup'];
        $descriptions = $contentType->getDescriptions();

        $schema
            [$this->getGroupName($contentTypeGroup)]
            ['config']['fields']
            [$this->getContentCollectionField($contentType)] = [
                'type' => sprintf("[%s]", $this->getContentName($contentType)),
                // @todo Improve description to mention that it is a collection ?
                'description' => isset($descriptions['eng-GB']) ? $descriptions['eng-GB'] : 'No description available',
                'resolve' => sprintf(
                    '@=resolver("DomainContentItemsByTypeIdentifier", ["%s", args])',
                    $contentType->identifier
                ),
                'args' => [
                    'query' => [
                        'type' => "ContentSearchQuery",
                        'description' => "A Content query used to filter results"
                    ],
                ],
            ];

        $schema
            [$this->getGroupName($contentTypeGroup)]
            ['config']['fields']
            [$this->getContentField($contentType)] = [
                'type' => $this->getContentName($contentType),
                'description' => isset($descriptions['eng-GB']) ? $descriptions['eng-GB'] : 'No description available',
                'resolve' => sprintf('@=resolver("DomainContentItem", [args["id"], "%s"])', $contentType->identifier),
                'args' => [
                    // @todo How do we constraint this so that it only takes an id of an item of that type ?
                    // same approach than GlobalId ? (<type>-<id>)
                    'id' => [
                        'type' => 'Int',
                        'description' => 'A content id'
                    ],
                ]
            ];
    }

    public function canWork(array $schema, array $args)
    {
        return
            isset($args['ContentType'])
            && $args['ContentType'] instanceof ContentType
            && isset($args['ContentTypeGroup'])
            && $args['ContentTypeGroup'] instanceof ContentTypeGroup
            && !$this->isFieldDefined($args['ContentTypeGroup'], $args['ContentType']);
    }

    /**
     * @param $contentTypeGroup
     * @return string
     */
    protected function getGroupName($contentTypeGroup): string
    {
        return $this->getNameHelper()->domainGroupName($contentTypeGroup);
    }

    /**
     * @param $contentType
     * @return string
     */
    protected function getContentCollectionField($contentType): string
    {
        return $this->getNameHelper()->domainContentCollectionField($contentType);
    }

    /**
     * @param $contentType
     * @return string
     */
    protected function getContentField($contentType): string
    {
        return $this->getNameHelper()->domainContentField($contentType);
    }

    /**
     * @param $contentType
     * @return string
     */
    protected function getContentName($contentType): string
    {
        return $this->getNameHelper()->domainContentName($contentType);
    }

    private function isFieldDefined(ContentTypeGroup $contentTypeGroup, ContentType $contentType)
    {
        return isset(
            $schema
            [$this->getGroupName($contentTypeGroup)]
            ['config']['fields']
            [$this->getContentCollectionField($contentType)]
        );
    }
}