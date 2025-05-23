<?php
// override core en language system validation or define your own en language validation message
// ENGLISH language
return [
    'register_adminSubject'     => 'Novo utilizador registado em SoccerYou',
    'register_message'          => 'Caro Admin, <br>Novo utilizador( {field} ) está registado',
    'register_success'          => 'Utilizador registado com sucesso',
    'login_success'             => 'Início de sessão bem sucedido',
    'login_invalidCredentials'  => 'Credenciais de início de sessão inválidas',
    'logout_success'            => 'Terminou a sessão com sucesso',
    'password_changeSuccess'    => 'Palavra-passe alterada com sucesso.',
    'email_notExist'            => 'O correio eletrónico não existe.',
    'email_alreadyExist'        => 'O correio eletrónico já existe.',
    'forgot_success'            =>  'Uma hiperligação enviada para o seu e-mail. Inicie sessão com esta ligação e actualize a sua palavra-passe.',
    'invalid_link'              =>  'Ligação inválida',
    'access_denied'             =>  'acesso negado',
    'delete_user_success'       =>  'Utilizador eliminado com sucesso',
    'invalid_userID'            =>  'Introduzir dados de utilizador válidos',
    'profile_updatedSuccess'    =>  'Perfil atualizado com sucesso',
    'profile_updatedfailure'    =>  'O seu perfil não foi atualizado. Por favor, tente novamente.',
    'uploaded'                  => 'Carregue um ficheiro válido. Só são permitidos ficheiros “{file}”.',
    'is_image'                  => 'O ficheiro carregado não é uma imagem. Só são permitidos ficheiros “{file}”.',
    'mime_in'                   => 'O tipo de ficheiro carregado não é permitido. Só são permitidos ficheiros “{file}”.',
    'ext_in'                    => 'O tipo de ficheiro carregado não é permitido. Só são permitidos ficheiros “{file}”.',
    'max_size'                  => 'O tamanho do ficheiro carregado é demasiado grande. O ficheiro deve ser inferior a “{size}” KB.',
    'max_dims'                  => 'As dimensões do ficheiro carregado são demasiado grandes.',
    'already_moved'             => 'O ficheiro já foi movido.',
    'file_upload_success'       => 'O ficheiro {file} foi carregado com sucesso.',
    'database_save_failure'     => 'Falha ao guardar o ficheiro {file} na base de dados.',
    'file_move_failure'         => 'Falha ao mover o ficheiro {file}.',
    'file_invalid'              => 'O ficheiro {file} é inválido ou já foi movido.',
    'file_already_moved'        => 'O ficheiro {file} já foi movido.',
    'no_files_uploaded'         => 'Não foram carregados quaisquer ficheiros.',
    'file_too_large'            => 'O ficheiro {file} excede o tamanho máximo permitido de {size}.',
    'file_type_not_allowed'     => 'O tipo de ficheiro {file} não é permitido. Apenas {ext} são permitidos.',
    'adminAccessDenied'         => 'Só o administrador pode aceder a esta página.',
    'playerAccessDenied'        => 'Só os jogadores podem aceder a esta página.',
    'clubAccessDenied'          => 'Apenas o clube pode aceder a esta página.',
    'scoutAccessDenied'         => 'Só os escuteiros podem aceder a esta página.',
    'dataFound'                 => 'Dados encontrados',
    'noDataFound'               => 'Não foram encontrados dados',
    'provideValidData'          => 'Forneça dados válidos.',
    'playersAdded'              => 'Jogadores adicionados ao clube com sucesso.',
    'recordNotExist'            => 'O registo não existe',
    'playersUpdated'            => 'Jogadores actualizados no clube com sucesso.',
    'playersUpdatedFailed'      => 'Falha ao atualizar os jogadores do clube. Por favor, tente novamente.',
    'clubPlayerDeleted'         => 'Jogador do clube eliminado',
    'clubPlayerDeleteFailed'    => 'Jogador do clube não eliminado. Por favor, tente novamente.',
    'scoutPlayersAdded'         => 'Jogador adicionado com sucesso como olheiro.',
    'clubPlayersUpdated'        => 'Jogadores actualizados no clube com sucesso.',
    'clubPlayersUpdateFailed'   => 'Falha ao atualizar os jogadores do clube. Por favor, tente novamente.',
    'playersAddedScout'         => 'Jogador adicionado com sucesso.',
    'playerDeleteScout'         => 'Jogador removido do scout.',
    'playerDeleteScoutFailed'   => 'O jogador não foi removido do olheiro. Por favor, tente novamente.',
    'error'                     => 'Erro',
    'favoriteAlreadyAdded'      => 'Favorito já adicionado',
    // 'favoriteAdded'             => 'Favorito adicionado com sucesso',
    'favoriteAddedFailed'       => 'Favorito não adicionado. Por favor, tente novamente.',
    'userDeletedFavorites'      => 'Removido dos favoritos com êxito',
    'userDeletedFavoritesFailed' => 'Favoritos não removidos. Por favor, tente novamente.',
    'provideDeleteData'         => 'Dados não fornecidos para eliminação.',
    'selectedFilesDeleted'      => 'Os ficheiros selecionados são eliminados.',
    'selectedFilesDeleteFailed' => 'Ficheiro(s) selecionado(s) não eliminado(s). Por favor, tente novamente.',
    'provideFeaturedFiles'      => 'Forneça o ID dos ficheiros apresentados.',
    'featuredFilesSet'          => 'Os ficheiros selecionados são assinalados como destacados.',
    'featuredFilesSetFailed'    => 'Ficheiros em destaque não actualizados. Tente novamente.',
    'featuredLimitExceeded'     => 'Limite de ficheiros em destaque excedido.',
    'performanceAdded'          => 'Adicionado pormenor de desempenho.',
    'performanceAddedFailed'    => 'Falha ao adicionar detalhes de desempenho. Tente novamente.',
    'performanceDeleted'        => 'Detalhes do desempenho eliminados.',
    'performanceDeleteFailed'   => 'O pormenor do desempenho não foi eliminado. Tente novamente.',
    'performanceUpdated'        => 'Detalhe do desempenho atualizado.',
    'performanceUpdateFailed'   => 'Falha ao atualizar os detalhes do desempenho. Tente novamente.',
    'performanceReportUploaded' => 'Relatório de desempenho carregado.',
    'performanceReportUploadFailed' => 'Falha ao carregar o relatório de desempenho.',
    'performanceReportDeleted'  => 'Relatório de desempenho eliminado com êxito.',
    'performanceReportDeleteFailed'  => 'Relatório de desempenho não eliminado. Por favor, tente novamente.',
    'sightingInvitesAdded'      => 'Convites para observação adicionados com sucesso.',
    'sightingInvitesAddFailed'  =>  'Falha ao adicionar o avistamento. Por favor, tente novamente.',
    'sightingInvitesDeleted'    => 'Convites para observação eliminados com sucesso.',
    'sightingInvitesDeletedFailed' => 'Os convites para observação não foram eliminados. Por favor, tente novamente.',
    'sightingCoverDeleted'      => 'A imagem de capa do avistamento foi eliminada com êxito.',
    'sightingCoverDeletedFailed' => 'A imagem da capa de observação não foi eliminada. Por favor, tente novamente.',
    'sightingAdded'             => 'Avistamento adicionado com sucesso.',
    'sightingAddFailed'         => 'Falha ao adicionar o avistamento. Por favor, tente novamente.',
    'sightingDeleted'           => 'Avistamento eliminado com sucesso.',
    'sightingDeletedFailed'     => 'Avistamento não eliminado. Por favor, tente novamente.',
    'contactFormSuccess' => 'Obrigado por entrar em contato! Retornaremos em breve.',
    'contactFormError'   => 'Ops! Algo deu errado. Por favor, tente novamente mais tarde.',
    // By amrit
    'popupAdded'                => 'Popup do sistema adicionado com sucesso.',
    'popupAddedFailed'          => 'Popup do sistema não foi adicionado. Por favor, tente novamente.',
    'popupUpdated'              => 'Popup do sistema atualizado com sucesso.',
    'popupUpdatedFailed'        => 'Popup do sistema não foi atualizado. Por favor, tente novamente.',
    'popupUpDeleted'            => 'Popup do sistema deletado com sucesso.',
    'popupUpDeletedFailed'      => 'Popup não foi deletado. Por favor, tente novamente.',
    'emailTemplateAdded'        => 'Modelo de email adicionado com sucesso.',
    'emailTemplateAddFailed'    => 'Modelo de email não foi adicionado. Por favor, tente novamente.',
    'emailTemplateUpdated'      => 'Modelo de email atualizado com sucesso.',
    'emailTemplateUpdateFailed' => 'Modelo de email não foi atualizado. Por favor, tente novamente.',
    'emailTemplateDeleted'      => 'Modelo de email deletado com sucesso.',
    'emailTemplateDeleteFailed' => 'Modelo de email não foi deletado. Por favor, tente novamente.',
    'couponAdded'               => 'Cupão adicionado com sucesso.',
    'couponAddFailed'           => 'Cupão não foi adicionado. Por favor, tente novamente.',
    'couponUpdated'             => 'Cupão atualizado com sucesso.',
    'couponUpdateFailed'        => 'Cupão não foi atualizado. Por favor, tente novamente.',
    'couponDeleted'             => 'Cupão deletado com sucesso.',
    'couponDeleteFailed'        => 'Cupão não foi deletado. Por favor, tente novamente.',
    'couponPublished'           => 'Cupão publicado com sucesso.',
    'couponPublishFailed'       => 'Cupão não foi publicado. Por favor, tente novamente.',
    'couponDraft'               => 'Rascunho do cupão criado com sucesso.',
    'couponDraftFailed'         => 'Rascunho do cupão não foi criado. Por favor, tente novamente.',
    'couponExpired'             => 'Cupão expirado com sucesso.',
    'couponExpireFailed'        => 'Cupão não expirou. Por favor, tente novamente.',
    'blogAdded'                 => 'Blog adicionado com sucesso.',
    'blogAddFailed'             => 'Blog não foi adicionado. Por favor, tente novamente.',
    'blogUpdated'               => 'Blog atualizado com sucesso.',
    'blogUpdateFailed'          => 'Blog não foi atualizado. Por favor, tente novamente.',
    'blogDeleted'               => 'Blog deletado com sucesso.',
    'blogDeleteFailed'          => 'Blog não foi deletado. Por favor, tente novamente.',
    'blogPublished'             => 'Blog publicado com sucesso.',
    'blogPublishFailed'         => 'Blog não foi publicado. Por favor, tente novamente.',
    'blogDraft'                 => 'Rascunho do blog criado com sucesso.',
    'blogDraftFailed'           => 'Rascunho do blog não foi criado. Por favor, tente novamente.',
    'advertisementAdded'        => 'Publicidade adicionada com sucesso.',
    'advertisementAddFailed'    => 'Publicidade não foi adicionada. Por favor, tente novamente.',
    'advertisementupdated'      => 'Publicidade atualizada com sucesso.',
    'advertisementupdateFailed' => 'Publicidade não foi atualizada. Por favor, tente novamente.',
    'advertisementDeleted'      => 'Publicidade deletada com sucesso.',
    'advertisementDeleteFailed' => 'Publicidade não foi deletada. Por favor, tente novamente.',
    'advertisementPublished'    => 'Publicidade publicada com sucesso.',
    'advertisementPublishFailed' => 'Publicidade não foi publicada. Por favor, tente novamente.',
    'advertisementDraft'        => 'Rascunho da publicidade criado com sucesso.',
    'advertisementDraftFailed'  => 'Rascunho da publicidade não foi criado. Por favor, tente novamente.',
    'advertisementExpired'      => 'Publicidade expirou com sucesso.',
    'advertisementExpireFailed' => 'Publicidade não expirou. Por favor, tente novamente.',
    'pageAdded'                 => 'Página adicionada com sucesso.',
    'pageAddFailed'             => 'Página não foi adicionada. Por favor, tente novamente.',
    'pageUpdated'               => 'Página atualizada com sucesso.',
    'pageUpdateFailed'          => 'Página não foi atualizada. Por favor, tente novamente.',
    'pageDeleted'               => 'A página {pageName} foi deletada com sucesso.',
    'pageDeleteFailed'          => 'Página não foi deletada. Por favor, tente novamente.',
    'pagePublished'             => 'Página publicada com sucesso.',
    'pagePublishFailed'         => 'Página não foi publicada. Por favor, tente novamente.',
    'pageDraft'                 => 'Rascunho da página criado com sucesso.',
    'pageDraftFailed'           => 'Rascunho da página não foi criado. Por favor, tente novamente.',
    'register_failed'           => 'Falha no registro. Por favor, tente novamente.',
    'club_application_success'  => 'Sua inscrição para registro no clube foi enviada com sucesso. Você recebeu um email, por favor verifique seu email.',
    'register_failed'           => 'Sua inscrição para registro no clube não foi enviada. Por favor, tente novamente.',
    'email_verified'            => 'Email verificado com sucesso.',
    'email_user_exist'          => 'Email ou nome de usuário já existem.',
    'representatorAdded'        => 'Representante adicionado com sucesso.',
    'representatorAddFailed'    => 'Representante não foi adicionado. Por favor, tente novamente.',
    'representatorAccessDenied' => 'Apenas Representantes podem acessar esta página.',
    'representatorRoleUpdateSuccess' => 'Função do representante atualizada com sucesso.',
    'representatorRoleUpdateFailed'  => 'Função do representante não foi atualizada. Por favor, tente novamente.',
    'unauthorized_access'       => 'Você não tem permissão para acessar esta página.',
    'newsletterSubscribed'      => 'Inscrição na newsletter realizada com sucesso.',
    'newsletterUnsubscribed'    => 'Desinscrição da newsletter realizada com sucesso.',
    'newsletterFailed'          => 'Falha ao se inscrever na newsletter. Por favor, tente novamente.',
    'file_deleteSuccess'        => 'Seu arquivo foi deletado com sucesso.',
    'file_deleteError'          => 'Erro ao deletar o arquivo.',
    'recordExist'               => 'Registro já existe.',
    'recordNotFound'            => 'Nenhum registro encontrado.',
    'companyHistoryAdded'       => 'História da empresa adicionada.',
    'companyHistoryAddFailed'   => 'História da empresa não foi adicionada.',
    'companyHistoryUpdated'     => 'História da empresa atualizada.',
    'companyHistoryUpdateFailed' => 'História da empresa não foi atualizada.',
    'clubHistoryAdded'          => 'História do clube adicionada.',
    'clubHistoryAddFailed'      => 'História do clube não foi adicionada.',
    'clubHistoryUpdated'        => 'História do clube atualizada.',
    'clubHistoryUpdateFailed'   => 'História do clube não foi atualizada.',
    'sightingCoverUpdated'      => 'Foto de capa da avistamento atualizada com sucesso.',
    'sightingCoverUpdateFailed' => 'Falha ao atualizar foto de capa.',
    'sightingDetailUpdated'     => 'Detalhes do avistamento atualizados com sucesso.',
    'sightingDetailUpdateFailed' => 'Falha ao atualizar detalhes do avistamento.',
    'sightingAboutUpdated'      => 'Detalhes sobre o avistamento atualizados com sucesso.',
    'sightingAboutUpdateFailed' => 'Falha ao atualizar detalhes sobre o avistamento.',
    'sightingUploadAttachment'  => 'Por favor, carregue pelo menos um anexo.',
    'sightingAttachmentUpdated' => 'Anexos atualizados com sucesso.',
    'sightingAttachmentUpdateFailed' => 'Anexos não foram atualizados. Por favor, tente novamente.',
    'sightingAttachmentDeleted' => 'Anexo de avistamento deletado com sucesso.',
    'sightingAttachmentDeleteFailed' => 'Anexo de avistamento não foi deletado. Por favor, tente novamente.',
    'homepage_updatedSuccess'   => 'Detalhes da página inicial atualizados com sucesso.',
    'homepage_updatedfailure'   => 'Falha ao atualizar a página inicial. Por favor, tente novamente.',
    'invalidParam'              => 'Parâmetros inválidos fornecidos. Por favor, verifique sua entrada e tente novamente.',
    'tabSectionSaved'           => 'Seção de aba salva com sucesso.',
    'permissionDenied'          => 'Você não tem permissões suficientes para realizar esta ação.',
    'teamTransferAdded'         => 'Detalhes da transferência de equipe adicionados com sucesso.',
    'teamTransferAddFailed'     => 'Falha ao adicionar detalhes da transferência de equipe. Por favor, tente novamente.',
    'teamTransferUpdated'       => 'Detalhes da transferência de equipe atualizados com sucesso.',
    'teamTransferUpdateFailed'  => 'Falha ao atualizar detalhes da transferência de equipe. Por favor, tente novamente.',
    'teamTransferDeleted'       => 'Detalhes da transferência deletados com sucesso.',
    'teamTransferDeleteFailed'  => 'Detalhes da transferência não foram deletados. Por favor, tente novamente.',
    'statusUpdated'             => 'Status atualizado com sucesso.',
    'statusUpdateFailed'        => 'Status não atualizado devido a um problema. Por favor, tente novamente.',
    'contact_page_updatedSuccess' => 'Página de contato atualizada com sucesso.',
    'news_page_updatedSuccess'  => 'Página de notícias atualizada com sucesso.',
    'aboutpage_updatedSuccess'  => 'Página sobre atualizada com sucesso.',
    'pricingpage_updatedSuccess' => 'Página de preços atualizada com sucesso.',
    'faq_page_updatedSuccess'   => 'Página de FAQ atualizada com sucesso.',
    'contentpage_updatedfailure' => 'Falha ao atualizar a página de conteúdo.',
    'talentpage_updatedSuccess' => 'Página de talentos atualizada com sucesso.',
    'club_scout_page_updatedSuccess' => 'Página de scout de clube atualizada com sucesso.',
    'boosterDataAdded'          => 'Detalhes do booster adicionados com sucesso.',
    'boosterDataAddFailed'      => 'Falha ao adicionar detalhes do booster. Por favor, tente novamente.',
    'AudienceUpdated'           => 'Dados da audiência atualizados com sucesso.',
    'noActivePackage'           => 'Nenhum pacote ativo.',
    'intentCreatedSuccess'      => 'Intenção criada com sucesso.',
    'intentCreateFailed'        => 'Falha ao criar intenção de pagamento.',
    'stripeError'               => 'Erro do Stripe.',
    'invalidPackageId'          => 'ID do pacote inválido.',
    'providePackageId'          => 'Por favor, forneça o ID do pacote.',
    'subscriptionCreatedSuccess' => 'Assinatura criada com sucesso.',
    'subscriptionUpdatedSuccess' => 'Assinatura atualizada com sucesso.',
    'subscriptionUpdateFailed'  => 'Falha ao atualizar assinatura. Por favor, tente novamente.',
    'subscriptionCancelledSuccess' => 'Assinatura cancelada com sucesso.',
    'subscriptionCancelFailed'  => 'Falha ao cancelar assinatura. Por favor, tente novamente.',
    'subscriptionNotFound'      => 'ID da assinatura não encontrado.',
    'subscriptioncancelError'   => 'Erro ao cancelar assinatura.',
    'customerExist'             => 'Cliente já existe.',
    'customerCreatedSuccess'    => 'Cliente criado com sucesso.',
    'packageAlreadyActivated'   => 'Este pacote já está ativado.',
    'invalidCouponApplied'      => 'Cupão inválido aplicado.',
    'couponNotExist'            => 'Este cupão não existe.',
    'couponNoMoreValid'         => 'Este cupão não é mais válido.',
    'couponLimitExceeded'       => 'Limite de uso do cupão excedido.',
    'couponApplyOnce'           => 'Este cupão pode ser usado apenas uma vez, você já o usou.',
    'couponValid'               => 'Este cupão é válido.',
    'couponNotAvailable'        => 'Este cupão ainda não está disponível.',
    // Pdf Heading Keywords
    'current_club' => 'Clube Atual',
    'height' => 'Altura',
    'weight' => 'Peso',
    'age' => 'Idade',
    'in_team_since' => 'No time desde',
    'top_speed' => 'Velocidade Máxima',
    'nationality' => 'Nacionalidade',
    'market_value' => 'Valor de Mercado Atual',
    'international_player' => 'Jogador Internacional',
    'dob' => 'Data de Nascimento',
    'last_change' => 'Última mudança',
    'place_of_birth' => 'Local de Nascimento',
    'main_position' => 'Posição Principal',
    'other_position' => 'Outra(s) posição(ões)',
    'contract' => 'Contrato',
    'leauge' => 'Liga',
    'foot' => 'Pé',
    'transfer_history' => 'Histórico de Transferências',
    'saison' => 'Temporada',
    'date' => 'Data',
    'performance_data' => 'Dados de Desempenho',
    'team' => 'Equipe',
    'matches' => 'Partidas',
    'goals' => 'Gols',
    'designation' => 'Designação',
    'company_name' => 'Nome da Empresa',
    'contact_number' => 'Número de Contato',
    'address' => 'Endereço',
    'zip_code' => 'Código Postal',
    'city' => 'Cidade',
    'website' => 'Website',
    'country' => 'País',
    'portfolio' => 'Portfólio',
    'name' => 'Nome',
    'language' => 'Idioma',
    'club' => 'Clube',
    'club_history' => 'História do Clube',
    'team_name' => 'Nome da Equipe',
    'player_name' => 'Nome do Jogador',
    'joining_date' => 'Data de Entrada',
    'exit_date' => 'Data de Saída',
    'location' => 'Localização',
    'moving_from' => 'Saindo de',
    'moving_to' => 'Indo para',
    'about_scout' => 'Sobre o Scout',
    'userCsvDownloadMessage' => 'Olá [userFullName],<br><br>Você pode baixar o arquivo CSV do usuário através do seguinte link:<br><a href="[CSV_LINK]">Baixar CSV do Usuário</a><br><br>',
    'password_changeFailed' => 'Falha ao alterar a senha. Por favor, tente novamente.',
    'accountDeletedSuccess' => 'Conta deletada com sucesso.',
    'accountDeleteFailed'   => 'Falha ao deletar conta. Por favor, tente novamente.',
    'inviteResponseUpdated'      => 'Sua resposta foi atualizada com sucesso.',
    'inviteResponseFailed'       => 'Sua resposta não foi atualizada. Por favor, tente novamente.',
    'invalidInvite'              => 'Parece que este convite não existe.',
    'verifyEmail'                => 'Seu email ({userEmail}) foi verificado com sucesso.',
    'favoriteAdded'              => 'Adicionado [USER_NAME_{userID}] aos favoritos.',
    'favoriteRemoved'            => 'Removido [USER_NAME_{userID}] dos favoritos.',
    'favoriteCsvDownload'        => 'CSV dos favoritos baixado pelo usuário.',
    'galleryFileUpload'          => 'Arquivo {imageName} carregado na galeria.',
    'setFeaturedFile'            => '{imageName} definido como arquivo em destaque.',
    'removeFeaturedFile'         => 'Removido do arquivo em destaque.',
    'fileDeleteSuccess'          => 'Arquivo {imageName} deletado da galeria com sucesso.',
    'addedInClub'                => 'Adicionado ao clube.',
    'playerExistInClub'          => 'Já existe na lista de jogadores.',
    'fileCreatedSuccess'         => 'Arquivo criado com sucesso.',
    'updateUserStatus'           => 'Status do perfil de {userName} atualizado para {status}.',
    'profile_image_updated'      => 'Imagem de perfil de [USER_NAME_{userID}] atualizada com sucesso ({imageName}).',
    'emailAlreadyVerified'       => 'Este e-mail já foi verificado', 
    'advertisementExist'            => 'O anúncio já existe',

    //Portuguese  
];
