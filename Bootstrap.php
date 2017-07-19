<?php

class Shopware_Plugins_Frontend_OssMediaAttributeDispatch_Bootstrap extends Shopware_Components_Plugin_Bootstrap {
    /**
     * Helper for availability capabilities
     * @return array
     */
    public function getCapabilities(){
        return array(
            'install' => true,
            'update' => true,
            'enable' => true,
        );
    }

    /**
     * Returns the meta information about the plugin.
     *
     * @return array
     */
    public function getInfo()
    {
        return array(
            'version' => $this->getVersion(),
            'author' => 'Odessite',
            'supplier' => 'Odessite',
            'label' => $this->getLabel(),
            'description' => file_get_contents(__DIR__ . '/description.html'),
            'copyright' => 'Copyright &copy; '.date('Y').', Odessite',
            'support' => 'info@shopwarrior.net',
            'link' => 'http://odessite.com.ua/'
        );
    }

    /**
     * Returns the version of plugin as string.
     *
     * @return string
     */
    public function getVersion() {
        return '1.0.0';
    }

    /**
     * Returns the plugin name for backend
     *
     * @return string
     */
    public function getLabel() {
        return 'Odessite Media Attribute Dispatch';
    }

    /**
     * Standard plugin install method to register all required components.
     * @return array
     */
    public function install() {
        try {
            $this->subscribeEvents()
                ->updateScheme();
        } catch(Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage(),
                'invalidateCache' => $this->getInvalidateCacheArray()
            );
        }

        return array(
            'success' => true,
            'message' => 'Plugin was successfully installed',
            'invalidateCache' => $this->getInvalidateCacheArray()
        );
    }

    /**
     * @return array
     */
    public function uninstall()
    {
        $this->dropScheme();

        return array(
            'success' => true,
            'message' => 'Plugin was successfully uninstalled',
            'invalidateCache' => $this->getInvalidateCacheArray()
        );
    }

    public function onPostDispatchFrontendListing(\Enlight_Event_EventArgs $args)
    {
        /** @var $controller \Enlight_Controller_Action */
        /** @var $view \Enlight_View_Default */
        /** @var $args \Enlight_Controller_ActionEventArgs */
        /** @var $req  \Enlight_Controller_Request_RequestHttp */
        $controller = $args->getSubject();
        $view = $controller->View();
        $response = $controller->Response();
        $request = $args->getRequest();
        $sCategoryContent = $view->getAssign('sCategoryContent');

        if (!$request->isDispatched()
            || $response->isException()
            || !in_array($request->getModuleName(), array( 'frontend' ) )
            || !$view->hasTemplate()
            || empty($sCategoryContent['attribute']['custombanner'])
        ) {
            return false;
        }

        $banners = [];
        $custombanners = explode( '|', trim($sCategoryContent['attribute']['custombanner'], '|') );
//        Fetch Banners
        try {
            $context = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext();
            $medias = Shopware()->Container()->get('shopware_storefront.media_service')->getList($custombanners, $context);
            foreach($custombanners as $bannerId){
                $banners[$bannerId] = Shopware()->Container()->get('legacy_struct_converter')->convertMediaStruct($medias[$bannerId]);
            }
        }catch(\Exception $e){
            Shopware()->Pluginlogger()->addError($e->getMessage(), $e->getTrace());
        }

        $view->addTemplateDir($this->Path() . 'Views/');
        $view->assign('ossCategoryBanners', $banners);
    }

    /**
     * @return Shopware_Plugins_Frontend_OssMediaAttributeDispatch_Bootstrap
     */
    private function subscribeEvents(){
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Frontend_Listing',
            'onPostDispatchFrontendListing'
        );

        return $this;
    }

    private function updateScheme(){
        /**@var \Shopware\Bundle\AttributeBundle\Service\CrudService $service **/
        $service = $this->get('shopware_attribute.crud_service');

        try{
            $service->update('s_categories_attributes', 'custombanner', 'multi_selection', [
                'label' => Shopware()->Snippets()->getNamespace("backend/common/main")->get(
                    'custombannerField', 'Landing Teaser', true
                ),
                'helpText' => Shopware()->Snippets()->getNamespace("backend/common/main")->get(
                    'custombannerDescrption', 'Landing Teaser Banner', true
                ),
                'entity' => 'Shopware\Models\Media\Media',
                'displayInBackend' => true,
                'position' => 1
            ]);
            Shopware()->Models()->generateAttributeModels( array('s_categories_attributes') );
        } catch(\Exception $e){
            Shopware()->Pluginlogger()->addError($e->getMessage(), $e->getTrace());
        }

    }

    private function dropScheme(){
        return;
        /**@var \Shopware\Bundle\AttributeBundle\Service\CrudService $service **/
        $service = $this->get('shopware_attribute.crud_service');

        try{
            $service->delete('s_categories_attributes', 'custombanner');
            Shopware()->Models()->generateAttributeModels( array('s_categories_attributes') );
        } catch(\Exception $e){
            Shopware()->Pluginlogger()->addError($e->getMessage(), $e->getTrace());
        }

    }

    /**
     * Helper for cache array
     * @return array
     */
    private function getInvalidateCacheArray()
    {
        return array('config', 'template', 'theme');
    }
}
