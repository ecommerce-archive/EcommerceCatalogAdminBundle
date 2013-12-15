<?php

namespace Ecommerce\Bundle\CatalogAdminBundle\Controller;

use Ecommerce\Bundle\CatalogBundle\Form\GroupedForm;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


// move to service
use Symfony\Component\Form\FormBuilder;
use Ecommerce\Bundle\CatalogBundle\Doctrine\Phpcr\Product;
use Ecommerce\Bundle\CatalogBundle\Form\DataMapper\NodeDataMapper;
// testing!!
use Doctrine\ODM\PHPCR\DocumentManager;
use Jackalope\Node;
use Doctrine\ODM\PHPCR\Document\Generic;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

class ProductController extends Controller
{
    private $propertyFiles;

    public function addPropertyFiles(array $files = array())
    {
        $this->propertyFiles = $files;

    }

    public function indexAction()
    {
//        $referenceRepository = $this->get('ecommerce_catalog.product_reference.repository');
//
//        $ref = $referenceRepository->find('6cf7bbe9-e69f-477c-bbe6-e80a8d48fa73');
//        $proxy = $ref->getProduct();
//
////        $repo = $this->get('ecommerce_catalog.persistence.phpcr.product.repository');
//
////        $proxy = $repo->getReference('6cf7bbe9-e69f-477c-bbe6-e80a8d48fa73');
//
//        return new Response($proxy->getName());


        $phpcr = $this->get('doctrine_phpcr');
//        $dm = $phpcr->getManager('default');
        /** @var DocumentManager $dm */
        $dm = $phpcr->getManager();

        /** @var Generic $productNode */
        $productNode = $dm->find(null, $this->get('service_container')->getParameter('ecommerce_catalog.persistence.phpcr.product_basepath'));
        $products = $productNode->getChildren();

        return $this->render(
            'EcommerceCatalogAdminBundle:Product:index.html.twig',
            array(
                'products' => $products)
        );
    }

    public function addAction(Request $request)
    {
        // Get create product form
        // Save product if valid

        $productManager = $this->get('ecommerce_catalog.product_manager');

        $formBuilder = $productManager->getCreateForm2();

        $formBuilder->add('create', 'submit', array('label' => 'Create product', 'attr' => array('class' => 'button')));
        $formBuilder->setAction($this->generateUrl('ecommerce_catalog_admin_product_add'));

        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {

            $productManager->commit();

            $product = $form->getData();

            if ($nodename = $product->get('name')) {
                $product->setNodename($nodename);
            }

            $productManager->save($product);

            return $this->redirect($this->generateUrl('ecommerce_catalog_admin_products'));
        }

        $productManager->rollback();

        return $this->render(
            'EcommerceCatalogAdminBundle:Product:add2.html.twig',
            array(
                'form' => $form->createView(),
            )
        );


        $productManager = $this->get('ecommerce_catalog.product_manager');

        $groupedForm = $productManager->getCreateForm();

        $groupedForm->getFormBuilder()->add('create', 'submit', array('label' => 'Create product', 'attr' => array('class' => 'button')));
        $groupedForm->getFormBuilder()->setAction($this->generateUrl('ecommerce_catalog_admin_product_add'));

        $form = $groupedForm->getForm();


        $form->handleRequest($request);

        if ($form->isValid()) {

//            $product = $form->getData();

//            if ($nodename = $product->get('name')) {
//                $product->setNodename($nodename);
//            }

//            $productManager->save($product);

//            return $this->redirect($this->generateUrl('ecommerce_catalog_admin_products'));
        }


        $form->createView();

        $productManager->rollback();


        return $this->render(
            'EcommerceCatalogAdminBundle:Product:add.html.twig',
            array(
                'grouped_form' => $groupedForm->createView(),
            )
        );




        $phpcr = $this->get('doctrine_phpcr');
//        $dm = $phpcr->getManager('default');
        /** @var DocumentManager $dm */
        $dm = $phpcr->getManager();


//        $product = $this->getProductManager()->findByName('JUC762');
        $productNode = $dm->find(null, $this->get('service_container')->getParameter('ecommerce_catalog.persistence.phpcr.product_basepath'));

        $product = new Product(uniqid('__tmp'), $productNode);

        $uow = $dm->getUnitOfWork();
        $session = $dm->getPhpcrSession();

        $utx = $session->getWorkspace()->getTransactionManager();
        if (!$utx->inTransaction()) {
            $utx->begin();
        } else {
            $utx = null;
        }

        $dm->persist($product);
        $dm->flush();


        /** @var FormBuilder $formBuilder */
        $formBuilder = $this->get('form.factory')->createNamedBuilder('product_create', 'form', $product, array('data_class' => 'Ecommerce\Bundle\CatalogBundle\Doctrine\Phpcr\Product')); // @todo config
        $formBuilder->setAction($this->generateUrl('ecommerce_catalog_admin_product_add'));
        $formBuilder->setDataMapper(new NodeDataMapper());

        $formGroups = array();

//        $data = Yaml::parse($this->get('service_container')->getParameter('kernel.root_dir').'/config/ecommerce/properties.yml');
//        $data = Yaml::parse($this->get('service_container')->getParameter('kernel.root_dir').'/config/ecommerce/properties.yml');
        $data = $this->get('ecommerce_catalog.product_properties_registry')->getProperties();


        foreach ($data as $group => $properties) {
            foreach ($properties as $propertyName => $propertyOptions) {
                if ($fallback = false && !isset($propertyOptions['form_type'])) {
                    throw new \RuntimeException(sprintf('Property %s is missing the form_type option', $propertyName));
                }
                if (!isset($formGroups[$group])) {
                    $formGroups[$group] = array();
                }
                $formGroups[$group][] = $propertyName;
                $formBuilder->add($propertyName, isset($propertyOptions['form_type']) ? $propertyOptions['form_type'] : 'text', isset($propertyOptions['form_options']) ? $propertyOptions['form_options'] : array());
            }

        }

        $formBuilder->add('create', 'submit', array('label' => 'Create product', 'attr' => array('class' => 'button')));

        $form = $formBuilder->getForm();


        $form->handleRequest($request);

        if ($form->isValid()) {

            $stop = 1;

            if ($nodename = $product->get('name')) {
                $product->setNodename($nodename);
            }

            $dm->persist($product);
            $dm->flush();


            if ($utx) {
                $utx->commit();
            }

            return $this->redirect($this->generateUrl('ecommerce_catalog_admin_products'));
            return $this->redirect($this->generateUrl('ecommerce_catalog_admin_product_add'));
        }


        if ($utx) {
            $utx->rollback();
        }


        return $this->render(
            'EcommerceCatalogAdminBundle:Product:add.html.twig',
            array(
                'form'        => $form->createView(),
                'form_groups' => $formGroups,
                'product'     => $product,
            )
        );
    }

    public function editAction($id, Request $request)
    {
        $productManager = $this->get('ecommerce_catalog.product_manager');

//        $product = $productManager->find(null, $this->get('service_container')->getParameter('ecommerce_catalog.persistence.phpcr.product_basepath').'/'.$id);
        $product = $productManager->findByName($id);

        $formBuilder = $productManager->getEditForm($product);

        $formBuilder->add('create', 'submit', array('label' => 'Create product', 'attr' => array('class' => 'button')));
        $formBuilder->setAction(
            $this->generateUrl(
                'ecommerce_catalog_admin_product_edit',
                array(
                    'id' => $product->getNodename(),
                )
            )
        );

        $form = $formBuilder->getForm();


        $form->handleRequest($request);

        if ($form->isValid()) {

            $productManager->save($product);

            return $this->redirect(
                $this->generateUrl(
                    'ecommerce_catalog_admin_product_edit',
                    array(
                        'id' => $product->getNodename(),
                    )
                )
            );
        }

        return $this->render(
            'EcommerceCatalogAdminBundle:Product:edit2.html.twig',
            array(
                'product'         => $product,
                'form'            => $form->createView(),
                'product_manager' => $productManager,
            )
        );




        $productManager = $this->get('ecommerce_catalog.product_manager');

//        $product = $productManager->find(null, $this->get('service_container')->getParameter('ecommerce_catalog.persistence.phpcr.product_basepath').'/'.$id);
        $product = $productManager->findByName($id);

        $groupedForm = $productManager->getEditForm($product);

        $groupedForm->getFormBuilder()->add('create', 'submit', array('label' => 'Create product', 'attr' => array('class' => 'button')));
        $groupedForm->getFormBuilder()->setAction(
            $this->generateUrl(
                'ecommerce_catalog_admin_product_edit',
                array(
                    'id' => $product->getNodename(),
                )
            )
        );

        $form = $groupedForm->getForm();


        $form->handleRequest($request);

        if ($form->isValid()) {

            $productManager->save($product);

            return $this->redirect(
                $this->generateUrl(
                    'ecommerce_catalog_admin_product_edit',
                    array(
                        'id' => $product->getNodename(),
                    )
                )
            );
        }

        return $this->render(
            'EcommerceCatalogAdminBundle:Product:edit.html.twig',
            array(
                'grouped_form' => $groupedForm->createView(),
            )
        );


        $phpcr = $this->get('doctrine_phpcr');
//        $dm = $phpcr->getManager('default');
        /** @var DocumentManager $dm */
        $dm = $phpcr->getManager();

        $product = $dm->find(null, $this->get('service_container')->getParameter('ecommerce_catalog.persistence.phpcr.product_basepath').'/'.$id);


        /** @var FormBuilder $formBuilder */
        $formBuilder = $this->get('form.factory')->createNamedBuilder('product_edit', 'form', $product, array('data_class' => 'Ecommerce\Bundle\CatalogBundle\Doctrine\Phpcr\Product')); // @todo config
        $groupedForm = new GroupedForm($formBuilder);
//        $groupedForm = new GroupedForm($this->get('form.factory')->createNamedBuilder('product_create', 'form', $product, array('data_class' => 'Ecommerce\Bundle\CatalogBundle\Doctrine\Phpcr\Product')));

        $groupedForm->getFormBuilder()->setAction(
            $this->generateUrl(
                'ecommerce_catalog_admin_product_edit',
                array(
                    'id' => $product->getNodename(),
                )
            )
        );
        $groupedForm->getFormBuilder()->setDataMapper(new NodeDataMapper());

        $formGroups = array();

        $data = array(
            'Def' => array(

                'name' =>
                    array(
//                        'form_type' => 'ecommerce_type_translated_property_text',
                        'form_type' => 'text',
                        'form_options' => array(
                            'label'    => 'Name',
                            'required' => false,
                        ),
                    ),

                'details' =>
                    array(
//                        'form_type' => 'ecommerce_type_translated_property_text',
                        'form_type' => 'text',
                        'form_options' => array(
                            'label'    => 'Details',
                            'required' => false,
                        ),
                    ),
            ),
            'Stanzen Gruppe' => array(
                'bim' =>
                    array(
                        'form_type' => 'text',
                    ),
                'purchaser' =>
                    array(
//                        'form_type' => 'entity_id',
                        'form_type' => 'integer',
                        'form_options' => array(
//                            'label'    => 'purchaser',
//                            'required' => false,
                            'inherit_data'  => false,
                        ),
                    ),
                'zak' =>
                    array(
                        'form_type' => 'choice',
                        'form_options' => array(
                            'label'    => 'Product type',
                            'choices'  => array(
                                'dress'     => 'Dress',
                                'bag'       => 'Handbag',
                                'jewelry'   => 'Jewelry',
                                'shapeware' => 'Shapeware'),
                            'multiple' => false,
                            'expanded' => true,
                            'required' => true,
                        ),
                    ),
            ),
        );


        foreach ($data as $group => $properties) {
            $formGroup = $groupedForm->with($group);
            foreach ($properties as $propertyName => $propertyOptions) {
//                if (!isset($propertyOptions['form_type'])) {
//                    throw new \RuntimeException(sprintf('Property %s is missing the form_type option', $propertyName));
//                }
//                if (!isset($formGroups[$group])) {
//                    $formGroups[$group] = array();
//                }
//                $formGroups[$group][] = $propertyName;
                $formGroup->add($propertyName, $propertyOptions['form_type'], isset($propertyOptions['form_options']) ? $propertyOptions['form_options'] : array());
            }
            $formGroup->end();
        }

        $groupedForm->getFormBuilder()->add('create', 'submit', array('label' => 'Create product', 'attr' => array('class' => 'button')));

        $form = $groupedForm->getForm();
        $form = $groupedForm->getForm();


        $form->handleRequest($request);

        if ($form->isValid()) {

            $dm->persist($product);
            $dm->flush();

            return $this->redirect(
                $this->generateUrl(
                    'ecommerce_catalog_admin_product_edit',
                    array(
                        'id' => $product->getNodename(),
                    )
                )
            );
        }

        return $this->render(
            'EcommerceCatalogAdminBundle:Product:edit.html.twig',
            array(
//                'form'        => $groupedForm->createView(),
                'grouped_form'        => $groupedForm->createView(),
//                'form_groups' => $formGroups,
                'product'     => $product,
            )
        );
    }
}
