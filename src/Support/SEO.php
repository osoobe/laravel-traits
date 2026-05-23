<?php

namespace  Osoobe\LaravelTraits\Support;

use Osoobe\Utilities\Helpers\Str;

trait SEO {

    public $seo_model_name;

    /**
     * SEO Route url
     *
     * @return string
     */
    public abstract function getRouteURL();

    /**
     * SEO title
     *
     * @return string
     */
    public abstract function getSEOTitleAttribute();
    
    /**
     * SEO description
     *
     * @return string
     */
    public function getSEODescriptionAttribute() {
        $parts = explode("\\", get_class($this));
        $subject = array_pop($parts);
        $subject = Str::ucsnake(Str::pluralStudly($subject), " ");
        $app_name = config('app.name');
        return "Check out $this->seo_title and other $subject on $app_name";
    }

    /**
     * URL
     *
     * @return string
     */
    public function getURLAttribute(){
        return $this->getRouteURL();
    }


}


?>
