<link rel="stylesheet" href="<?= base_url('assets/css/downloads.css?v='.time()); ?>">

<div class="dl-page-bg"></div>

<div class="dl-hero">
    <div class="dl-hero-bg" style="background-image: url('<?= $template['assets'].'core/css/images/dech7b4-261984f9-3751-478c-8733-e3e9429a2e5a.jpg'; ?>');"></div>
    <div class="dl-hero-overlay"></div>
    <div class="dl-hero-content cw-wrap">
        <div class="dl-hero-medallion"><i class="fas fa-download"></i></div>
        <h1><span>Téléchargements</span></h1>
        <p>Récupérez le client et les fichiers requis pour jouer</p>
    </div>
</div>

<div class="dl-layout">
    <div class="dl-container cw-wrap">
        
        <div class="dl-panel">
            <div uk-grid>
                
                <div class="uk-width-1-4@m dl-sidebar">
                    <ul class="uk-tab-left dl-tabs" uk-tab="connect: #dl-switcher; animation: uk-animation-fade">
                        <li><a href="#"><i class="fas fa-gamepad"></i> Client</a></li>
                        <li><a href="#"><i class="fas fa-puzzle-piece"></i> Addons</a></li>
                    </ul>
                </div>

                <div class="uk-width-3-4@m dl-content">
                    <ul id="dl-switcher" class="uk-switcher">
                        
                        <li>
                            <div class="dl-table-wrap">
                                <table class="dl-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 70px;"></th>
                                            <th>Nom du fichier</th>
                                            <th>Taille</th>
                                            <th>Type</th>
                                            <th style="text-align: right;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($this->download_model->getGame()->result() as $files): ?>
                                        <tr>
                                            <td>
                                                <div class="dl-icon" style="background-image:url(<?= base_url('assets/images/forums/wow-icons/'.$files->image); ?>);"></div>
                                            </td>
                                            <td class="dl-filename"><?= $files->fileName ?></td>
                                            <td class="dl-meta"><?= $files->weight ?></td>
                                            <td class="dl-meta"><?= $files->type ?></td>
                                            <td style="text-align: right;">
                                                <a class="btn-epic" href="<?= $files->url ?>" target="_blank"><i class="fas fa-download"></i> Télécharger</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </li>

                        <li>
                            <div class="dl-table-wrap">
                                <table class="dl-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 70px;"></th>
                                            <th>Nom</th>
                                            <th>Taille</th>
                                            <th>Date</th>
                                            <th>Description</th>
                                            <th style="text-align: right;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($this->download_model->getAddonswotlk()->result() as $files): ?>
                                        <tr>
                                            <td>
                                                <div class="dl-icon" style="background-image:url(<?= base_url('assets/images/forums/wow-icons/'.$files->image); ?>);"></div>
                                            </td>
                                            <td class="dl-filename"><?= $files->fileName ?></td>
                                            <td class="dl-meta"><?= $files->weight ?></td>
                                            <td class="dl-meta"><?= $files->date ?></td>
                                            <td class="dl-desc"><?= $files->descript ?></td>
                                            <td style="text-align: right;">
                                                <a class="btn-epic" href="<?= $files->url ?>" target="_blank"><i class="fas fa-download"></i> Télécharger</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </li>

                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>