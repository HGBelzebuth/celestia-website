<div class="Pane Pane--underSiteNav Pane--cropFull" data-url="<?= $template['location'].'assets/images/9VZFN0YMWTJW1458072663361.jpg'; ?>" media-medium="!hide" queryselectoralways="0 31" media-original="Pane Pane--underSiteNav Pane--cropFull hide">
<div class="Pane-bg" style="background-color:#041828;background-image:url(&quot;<?= $template['location'].'assets/images/9VZFN0YMWTJW1458072663361.jpg'; ?>&quot;);">
<div class="Pane-overlay">
</div></div><div class="space-large"></div><div class="space-large"></div>
<div class="Pane-content">
<div class="space-large">
</div>
<div class="gutter-huge">
        <h4 class="uk-h4 uk-text-uppercase uk-text-bold"><i class="fas fa-users"></i> <?=$this->lang->line('tab_online');?></h4>
        <ul class="uk-subnav uk-subnav-pill" uk-switcher="connect: .onlineplayers">
          <?php foreach ($realms as $realm): ?>
          <li><a href="#" style="font-size:13px"><i class="fas fa-server"></i> <?= $this->wowrealm->getRealmName($realm->realmID); ?></a></li>
          <?php endforeach; ?>
        </ul>
        <ul class="uk-switcher onlineplayers uk-margin-small">
          <?php foreach ($realms as $charsMultiRealm):
            $multiRealm = $this->wowrealm->getRealmConnectionData($charsMultiRealm->id);
          ?>
          <li>
            <div class="uk-overflow-auto uk-margin-small">
              <table class="uk-table dark-table uk-table-divider uk-table-small">
                <thead>
                  <tr>
                    <th class="uk-table-expand" style="font-size:13px"><i class="fas fa-user"></i> <?=$this->lang->line('table_header_name');?></th>
                    <th class="uk-table-expand uk-text-center" style="font-size:13px"><i class="fas fa-info-circle"></i> <?=$this->lang->line('table_header_level');?></th>
                    <th class="uk-table-expand uk-text-center" style="font-size:13px"><i class="fas fa-user-tag"></i> <?=$this->lang->line('table_header_race');?></th>
                    <th class="uk-table-expand uk-text-center" style="font-size:13px"><i class="fas fa-user-tag"></i> <?=$this->lang->line('table_header_class');?></th>
                    <th class="uk-table-expand uk-text-center" style="font-size:13px"><i class="fas fa-location-arrow"></i> <?=$this->lang->line('table_header_zone');?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($this->online_model->getOnlinePlayers($multiRealm)->result() as $online): ?>
                  <tr>
                    <td class="uk-text-capitalize" style="font-size:13px"><?= $online->name ?></td>
                    <td class="uk-text-center" style="font-size:13px"><?= $online->level ?></td>
                    <td class="uk-text-center" style="font-size:13px"><img class="uk-border-rounded" src="<?= base_url('assets/images/races/'.$this->wowgeneral->getRaceIcon($online->race)); ?>" width="20" height="20" title="<?= $this->wowgeneral->getRaceName($online->race); ?>" alt="<?= $this->wowgeneral->getRaceName($online->race); ?>"></td>
                    <td class="uk-text-center" style="font-size:13px"><img class="uk-border-rounded" src="<?= base_url('assets/images/class/'.$this->wowgeneral->getClassIcon($online->class)); ?>" width="20" height="20" title="<?= $this->wowgeneral->getClassName($online->class); ?>" alt="<?= $this->wowgeneral->getClassName($online->class); ?>"></td>
                    <td class="uk-text-center" style="font-size:13px"><?= $this->wowgeneral->getSpecifyZone($online->zone); ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div><div class="hide" media-wide="!hide">
									<div class="space-huge"></div>
									<div class="space-large"></div>
									<div class="space-large"></div>
									

									</div>
</div>
