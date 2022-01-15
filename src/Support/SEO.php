<?php

namespace  Osoobe\LaravelTraits\Support;


trait SEO {

    public abstract function getRouteURL();
    public abstract function getSEOTitleAttribute();
    public abstract function getSEODescriptionAttribute();

    public function getURLAttribute(){
        return $this->getRouteURL();
    }


}


?>
