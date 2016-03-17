<?php
namespace Civix\CoreBundle\Entity\Micropetitions;

use JMS\Serializer\Annotation as Serializer;

class Metadata
{
    /**
     * @var string
     * @Serializer\Expose()
     * @Serializer\Groups({
     *     "api-petitions-list",
     *     "api-petitions-info",
     *     "api-leader-micropetition",
     *     "api-activities"
     * })
     */
    private $title;

    /**
     * @var string
     * @Serializer\Expose()
     * @Serializer\Groups({
     *     "api-petitions-list",
     *     "api-petitions-info",
     *     "api-leader-micropetition",
     *     "api-activities"
     * })
     */
    private $description;

    /**
     * @var string
     * @Serializer\Expose()
     * @Serializer\Groups({
     *     "api-petitions-list",
     *     "api-petitions-info",
     *     "api-leader-micropetition",
     *     "api-activities"
     * })
     */
    private $image;

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Metadata
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Metadata
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $image
     * @return Metadata
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }
}