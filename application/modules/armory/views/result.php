<link rel="stylesheet" href="<?= base_url('application/modules/armory/assets/css/armory_search.css?v='.time()); ?>">
<div class="armory-page-bg"></div>

<section class="armory-hero">
    <div class="armory-hero-content">
        <div class="armory-hero-medallion"><i class="fas fa-shield-alt"></i></div>
        <h1>Armurerie</h1>
        <p>Résultats de recherche</p>
        <div class="armory-hero-line"></div>
    </div>
</section>

<div class="armory-layout">
    <div class="armory-container">

        <div class="armory-panel" style="margin-bottom:24px;">
            <div class="armory-panel-body">
                <?= form_open('armory/result', ['method' => 'get', 'id' => 'searcharmoryForm']); ?>
                <div class="armory-search-form">
                    <input class="armory-search-input" id="search" name="search" type="text"
                        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                        placeholder="Nom du joueur ou de la guilde…" required autocomplete="off">
                    <select class="armory-search-select" id="realm" name="realm">
                        <?php foreach ($realms as $r): ?>
                            <option value="<?= $r->realmID ?>"
                                <?= (isset($_GET['realm']) && $_GET['realm'] == $r->realmID) ? 'selected' : ''; ?>>
                                <?= $this->wowrealm->getRealmName($r->realmID); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="armory-search-btn">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                </div>
                <?= form_close(); ?>
            </div>
        </div>

        <?php if (!empty($_GET['search'])): ?>
        <?php foreach ($realms as $charsMultiRealm):
            $MultiRealm = $this->wowrealm->getRealmConnectionData($charsMultiRealm->id);
            $players = $this->armory_model->searchchar($MultiRealm, $search)->result();
            $guilds  = $this->armory_model->searchguild($MultiRealm, $search)->result();
        ?>
        <div class="armory-panel">
            <div class="armory-panel-header">
                <div class="armory-panel-header-icon"><i class="fas fa-list"></i></div>
                <h2>Résultats pour "<?= htmlspecialchars($search); ?>"</h2>
            </div>
            <div class="armory-panel-body">

                <div class="armory-tabs">
                    <button class="armory-tab-btn active" onclick="armoryTab(this,'tab-players')">
                        <i class="fas fa-user"></i> Joueurs
                        <span style="font-size:.8rem;opacity:.5;margin-left:4px;">(<?= count($players); ?>)</span>
                    </button>
                    <button class="armory-tab-btn" onclick="armoryTab(this,'tab-guilds')">
                        <i class="fas fa-shield-alt"></i> Guildes
                        <span style="font-size:.8rem;opacity:.5;margin-left:4px;">(<?= count($guilds); ?>)</span>
                    </button>
                </div>

                <div id="tab-players" class="armory-tab-content active">
                    <?php if (empty($players)): ?>
                        <div class="armory-empty">
                            <i class="fas fa-user-slash"></i>
                            <p>Aucun joueur trouvé.</p>
                        </div>
                    <?php else: ?>
                    <table class="armory-table">
                        <thead>
                            <tr>
                                <th>Joueur</th>
                                <th class="center">Niveau</th>
                                <th class="center">Race</th>
                                <th class="center">Classe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($players as $player): ?>
                            <tr>
                                <td><a href="<?= base_url('armory/player/' . $player->guid); ?>" class="armory-table-link"><?= htmlspecialchars($player->name); ?></a></td>
                                <td class="center"><?= $player->level; ?></td>
                                <td class="center"><img class="armory-table-icon" src="<?= base_url('assets/images/races/' . $this->wowgeneral->getRaceIcon($player->race)); ?>" title="<?= $this->wowgeneral->getRaceName($player->race); ?>" alt=""></td>
                                <td class="center"><img class="armory-table-icon" src="<?= base_url('assets/images/class/' . $this->wowgeneral->getClassIcon($player->class)); ?>" title="<?= $this->wowgeneral->getClassName($player->class); ?>" alt=""></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

                <div id="tab-guilds" class="armory-tab-content">
                    <?php if (empty($guilds)): ?>
                        <div class="armory-empty">
                            <i class="fas fa-shield-alt"></i>
                            <p>Aucune guilde trouvée.</p>
                        </div>
                    <?php else: ?>
                    <table class="armory-table">
                        <thead>
                            <tr>
                                <th>Guilde</th>
                                <th>Message de guilde</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($guilds as $guild): ?>
                            <tr>
                                <td><a href="<?= base_url('armory/guild/' . $guild->guildid); ?>" class="armory-table-link"><?= htmlspecialchars($guild->name); ?></a></td>
                                <td style="color:rgba(168,184,200,.55);font-style:italic;"><?= !empty($guild->motd) ? htmlspecialchars($guild->motd) : '—'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

    </div>
</div>

<script>
function armoryTab(btn, id) {
    document.querySelectorAll('.armory-tab-btn').forEach(function(b) { b.classList.remove('active'); });
    document.querySelectorAll('.armory-tab-content').forEach(function(c) { c.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById(id).classList.add('active');
}
</script>