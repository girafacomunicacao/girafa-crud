<?php
namespace Girafa\Crud;

return array(
    'router' => array(
        'routes' => array(
            // The following is a route to simplify getting started creating
            // new controllers and actions without needing to create a new
            // module. Simply drop new controllers in, and you can access them
            // using the path /ModuleTemplate/:controller/:action
            'crud' => array(				
                'type'    => 'Segment',
				'priority' => 10000,
                'options' => array(
					'route'    => '[/:module][/]',
					'constraints' => array(
						'module'	 => '[a-zA-Z][a-zA-Z0-9_-]*',						
					),
                    'defaults' => array(						
						'module'		=> 'application',
                        'controller'    => 'Index',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,				
                'child_routes' => array(
                    'default' => array(
						'type' => 'Segment',
						'options' => array(
							//'route'    => '[:controller[/:action]][/]',
							'route'    => '[:controller][/]',
							'constraints' => array(								
								'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',														
							),
							'defaults' => array(
								'controller'    => 'Index',
								'action'        => 'index',
							),
						),
						'may_terminate' => true,
						'child_routes' => array(
							'process' => array(
								'type' => 'Literal',
								'options' => array(
									'route'    => ':controller/process/',
									'defaults' => array(
										'action' => 'process',
									),
								),
							),
							'wildcard' => array(
								'type' => 'Wildcard',
								'options' => array(
									'key_value_delimiter' => '/',
									'param_delimiter' => '/',
								),								
							),
						),
					),
					
					'index' => array(
						'type' => 'Segment',
						'options' => array(
							'route' => ':controller[/:page]/'
						)
					),
					
					'new' => array(
						'type' => 'Segment',
						'options' => array(
							'route'    => ':controller/new/',
							'defaults' => array(
								'action' => 'new',
							),
						),
					),				

					'edit' => array(
						'type' => 'Segment',
						'options' => array(
							'route'    => ':controller/edit[/:id]/',
							'constraints' => array(
								'id'         => '[0-9]*',
							),
							'defaults' => array(
								'action' => 'edit',
							),
						),
					),

					'delete' => array(
						'type' => 'Segment',
						'options' => array(
							'route'    => ':controller/delete[/:id]/',
							'constraints' => array(
								'id'         => '[0-9]*',
							),
							'defaults' => array(
								'action' => 'delete',
							),
						),
					),

					'detail' => array(
						'type' => 'Segment',
						'options' => array(
							'route'    => ':controller/detail/:id[/:format]/',
							'constraints' => array(
								'id'         => '[0-9]*',
								'format'	 => 'html|json',
							),
							'defaults' => array(
								'action' => 'detail',
								'format' => 'html'
							),
						),
					),
					
					'crud' => array(
						'type' => 'Segment',
						'options' => array(
							'route'    => ':controller/:action[/:id]/',
							'constraints' => array(
								'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
								'id'         => '[0-9]*',
							),
							'defaults' => array(
								'action' => 'index',
							),
						),
					),
					
					'wildcard' => array(
						'type' => 'Wildcard',
						'options' => array(
							'key_value_delimiter' => '/',
							'param_delimiter' => '/',
						),								
					),
				),
            ),
        ),
    ),
	
    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
	'view_helpers' => array(
        'invokables' => array(
			'renderForm'			=> 'Girafa\Crud\View\Helper\RenderForm',
			'renderDefaultElement'	=> 'Girafa\Crud\View\Helper\RenderDefaultElement',
			'DisplayFlash'			=> 'Girafa\Crud\View\Helper\DisplayFlash',
			'datagrid'				=> 'Girafa\Crud\View\Helper\Datagrid',
		),
    ),
    
    'Crud' => array(
		'paginate'					=> true,
		'itensPerPage'				=> 30,
        's_indexTitle'				=> 'Index page Crud default',
        's_indexTemplate'			=> 'crud/index/index',
        's_newTitle'				=> 'New page Crud default',
        's_newTemplate'				=> 'crud/index/default-form',
		's_newRoute'				=> 'crud/new',
        's_editTitle'				=> 'Edit page Crud default',
        's_editTemplate'			=> 'crud/index/default-form',
        's_detailTitle'				=> 'Detail page Crud default',
        's_detailTemplate'			=> 'crud/index/detail',
        's_processErrorTitle'		=> 'Error page Crud default',
        's_processErrorTemplate'	=> 'crud/index/default-form',
        's_deleteRouteRedirect'		=> 'crud',
        's_processRouteRedirect'	=> 'crud',
		's_flashMessageNew'			=> 'Registro adicionado com sucesso',
		's_flashMessageUpdate'		=> 'Registro alterado com sucesso',
		's_flashMessageDelete'		=> 'Registro excluido com sucesso',
		's_flashMessageDelete'		=> '<strong>Erro!</strong> Não foi possível excluir o registro, tente novamente.',
		's_ajaxMessageNew'			=> 'Registro adicionado com sucesso',
		's_ajaxMessageUpdate'		=> 'Registro alterado com sucesso',
		's_ajaxMessageDelete'		=> 'Registro excluido com sucesso',
		's_ajaxMessagePersistError' => 'Ocorreu um erro ao gravar os dados. Error: %s',
		's_messagePersistError'		=> 'Ocorreu um erro ao gravar os dados. Error: %s',
		's_messageFormNotValid'		=> 'Alguns dados não foram preenchidos corretamente',
		's_messageNoResults'		=> 'Nenhum registro encontrado',	
		'showCreated'				=> true,
		'createdMethodGet'			=> 'getCreated',
		'timestampedDateFormat'		=> 'd/m/Y H:i:s',
		'orderBy'					=> array(
			'field'	=> 'id',
			'order' => 'DESC'
		),
		'datagridColumns'		 => array(			
			'actions' => array(
				'order'  => 999,
				'header' => 'Ações',
				'value'  => array(
					'type' => 'actions',
					'key' => array(
						'edit' => array(
							'attributes' => array(
								'title' => 'Editar',
							),
							'text' => '<i class="icon-pencil"></i>',
							'link' => array(
								'route' => 'crud/edit',
								'args' => array(
									'module' => 'application',
									'controller' => '__CONTROLLER__',
									'id' => array(
										'type' => 'property',
										'key' => 'id'
									)
								)
							)
						), 
						'delete' => array(
							'attributes' => array(
								'title' => 'Excluir',
								'role' => 'button',
							),
							'text' => '<i class="icon-remove"></i>',
							'link' => array(
								'route' => 'crud/delete',
								'args' => array(
									'module' => 'application',
									'controller' => '__CONTROLLER__',
									'id' => array(
										'type' => 'property',
										'key' => 'id'
									)
								)
							)
						),
					)
				)
			) 
		),
    ),
);