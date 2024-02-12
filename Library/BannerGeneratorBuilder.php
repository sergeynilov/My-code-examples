<?php

namespace App\Library\Builders;

use Exception;
use App\Library\Services\Interfaces\LogInterface;

/*
 Class to show banner with text, description, bannerLogoImage and image icon

 Example of use :
  $response = (new BannerGeneratorBuilder)
  // Set text((by default at left top corner)) with properties
    ->setText($bannerText)
    ->setTextColor($textColor)
    ->setTextFontFilePath('fonts/Shadowed/GrutchShaded.ttf')
    ->setTextXPoint(20)
    ->setTextYPoint(60)
    ->setTextSize(54)
    // Set images - background and logo image at right corner
    ->setBannerLogoImageFileUrl($bannerLogoImageMedia ? $bannerLogoImageMedia->getUrl() : '')
    ->setBgimageFileUrl($bannerBgimageMedia->getUrl())
    // Set description((by default at left bottom corner)) with properties
    ->setDescription($bannerDescription)
    ->setDescriptionFontFilePath('fonts/roboto/Roboto_regular.ttf')
    ->setDescriptionXPoint(20)
    ->setDescriptionYPoint(150)
    ->setDescriptionSize(24)
    ->generate(returnResponse: false)
    ->getResponse();

 * */
class BannerGeneratorBuilder
{
    protected bool $isDebug = false;
    protected $logger;
    protected $bgImageObject;
    protected $response;

    protected string $text;
    protected string $description;
    protected string $textColor;
    protected string $bannerLogoImageFileUrl;
    protected string $bgimageFileUrl;

    protected string $textFontFilePath = '';
    protected int $textSize = 50;
    protected int $descriptionSize = 50;

    protected string $descriptionFontFilePath;
    protected int $descriptionXPoint = 10;
    protected int $descriptionYPoint = 160;

    protected int $textXPoint = 10;
    protected int $textYPoint = 70;

    /**
     * Create a new BannerGeneratorBuilder instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->logger = app(LogInterface::class);
    }

    /*
    *
     * @param string $value - text which would be shown(by default at left top corner)
     *
     * @return self
     * */
    public function setText(string $value): self
    {
        $this->text = $value;

        return $this;
    }

    /*
     *
     * @param string $value - description text which would be shown(by default at left bottom corner)
     *
     * @return self
     * */
    public function setDescription(string $value): self
    {
        $this->description = $value;

        return $this;
    }

    /*
     *
     * @param string(hex value) $value - color for text and description text
     *
     * @return self
     * */
    public function setTextColor(string $value): self
    {
        $this->textColor = $value;

        return $this;
    }

    /*
     *
     * @param string (file url) $value - logo image (by default at right side)
     *
     * @return self
     * */
    public function setBannerLogoImageFileUrl(string $value): self
    {
        $this->bannerLogoImageFileUrl = $value;

        return $this;
    }

    /*
     *
     * @param string (file url) $value - banner background image
     *
     * @return self
     * */
    public function setBgimageFileUrl(string $value): self
    {
        $this->bgimageFileUrl = $value;

        return $this;
    }

    /*
     * Generate banner image
     *
     * @param bool $returnResponse - if true then response(image) returned. If false the object itself
     *
     * @return | self
     * */
    public function generate(bool $returnResponse = false)
    {
        try {
            $this->bgImageObject = \Image::make($this->bgimageFileUrl);
        } catch (\Intervention\Image\Exception\NotReadableException $e) {
            $this->logger->writeError(s:$e->getMessage(), file: __FILE__, line: __LINE__, info: 'NotReadableException generating banner');
            return $this;
        }

        // Write text and description on image area
        try {
            $this->bgImageObject->text($this->text, $this->getTextXPoint(), $this->getTextYPoint(),
                function ($font) {
                    if ( ! empty($this->textFontFilePath)) {
                        $font->file(public_path($this->textFontFilePath));
                    }
                    $font->size($this->getTextSize());
                    $font->color($this->textColor);
                });

            if ( ! empty($this->description)) {
                $this->bgImageObject->text($this->description, $this->getDescriptionXPoint(),
                    $this->getDescriptionYPoint(),
                    function ($font) {
                        if ( ! empty($this->descriptionFontFilePath)) {
                            $font->file(public_path($this->descriptionFontFilePath . ''));
                        }
                        $font->size($this->getDescriptionSize());
                        $font->color($this->textColor);
                    });
            }
        } catch (Exception $e) {
            $this->logger->writeInfo($this->getTextFontFilePath() . ', ');
            $this->logger->writeInfo($this->getDescriptionFontFilePath() . ', ');
            $this->logger->writeInfo($e->getMessage());
        }

        // Show logo image on image area
        $this->bgImageObject->insert($this->bannerLogoImageFileUrl, 'top-right', 15, 30);
        $this->response = $this->bgImageObject->psrResponse('png', 100);
        if ($returnResponse) {
            return $this->response;
        } else {
            return $this;
        }
    }

    /*
     *
     * @param string (file url) $value - banner background image
     * */
    public function getResponse()
    {
        return $this->response;
    }


    /*
     * @return string - description font file path
     * */
    public function getDescriptionFontFilePath(): string
    {
        return $this->descriptionFontFilePath;
    }

    /*
     * @var $value string - description font file path
     *
     * @return self
     * */
    public function setDescriptionFontFilePath(string $value   ): self
    {
        $this->descriptionFontFilePath = $value;

        return $this;
    }

    public function getTextFontFilePath(): string
    {
        return $this->textFontFilePath;
    }

    /*
     * @var $value string - text font file path
     *
     * @return self
     * */
    public function setTextFontFilePath(string $value): self
    {
        $this->textFontFilePath = $value;

        return $this;
    }

    public function getTextXPoint(): int
    {
        return $this->textXPoint;
    }

    /*
     * @var $value int - X coordinate of the text
     *
     * @return self
     * */
    public function setTextXPoint(int $value): self
    {
        $this->textXPoint = $value;

        return $this;
    }

    public function getTextYPoint(): int
    {
        return $this->textYPoint;
    }

    /*
     * @var $value int - Y coordinate of the text
     *
     * @return self
     * */
    public function setTextYPoint(int $value): self
    {
        $this->textYPoint = $value;

        return $this;
    }

    //////////////

    public function getDescriptionXPoint(): int
    {
        return $this->descriptionXPoint;
    }

    /*
     * @var $value int - X coordinate of the description
     *
     * @return self
     * */
    public function setDescriptionXPoint(int $value): self
    {
        $this->descriptionXPoint = $value;

        return $this;
    }

    public function getDescriptionYPoint(): int
    {
        return $this->descriptionYPoint;
    }

    /*
     * @var $value int - Y coordinate of the description
     *
     * @return self
     * */
    public function setDescriptionYPoint(int $value): self
    {
        $this->descriptionYPoint = $value;

        return $this;
    }


    public function getTextSize(): string
    {
        return $this->textSize;
    }

    /*
     * @var $value int - size of text
     *
     * @return self
     * */
    public function setTextSize(int $value ): self
    {
        $this->textSize = $value;

        return $this;
    }

    public function getDescriptionSize(): string
    {
        return $this->descriptionSize;
    }

    /*
     * @var $value int - size of description
     *
     * @return self
     * */
    public function setDescriptionSize(int $value): self {
        $this->descriptionSize = $value;

        return $this;
    }

}
