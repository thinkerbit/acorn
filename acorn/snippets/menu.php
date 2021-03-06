<?php $count = 1; ?>
<?php $number = sizeof(navArrayYAML()) ?>

<?php

  $activeTop = activeMenuItems()[0];
  $activeSub = activeMenuItems()[1];
  
?>

<nav id="navigation" class="newnav">
  <div class="container">
    
    <ul class="menu">
      
      <?php ///// LOGO ///// ?>
      <?php
        $logo     = site()->images()->findBy('name', 'logo');
        $logoIcon = site()->images()->findBy('name', 'logo-icon');
      ?>
      <?php if ($logo or $logoIcon): ?>
        <a class="logo" href="<?php echo url() ?>">
          
          <?php if ($logoIcon): ?>
            <div id="logo-2">
              
              <?php
                if ($logoIcon->extension() == 'svg') {
                  echo $logoIcon->content();
                } elseif (in_array($logoIcon->extension(), array('jpg','png','gif','ico','tiff','bmp'))) {
                  echo '<img src="' . $logoIcon->url() . '">';
                }
              ?>
              
            </div>
          <?php endif ?>
          
          <?php if ($logo): ?>
            <div id="logo-1">
              
              <?php
                if ($logo->extension() == 'svg') {
                  echo $logo->content();
                } elseif (in_array($logo->extension(), array('jpg','png','gif','ico','tiff','bmp'))) {
                  echo '<img src="' . $logo->url() . '">';
                }
              ?>
              
            </div>
          <?php endif ?>
          
        </a>
      <?php else: ?>
        <li><a href="<?php echo url() ?>">Home</a></li>
      <?php endif ?>
      
      <?php ///// PRIMARY MENU ITEMS ///// ?>
      <?php foreach (yaml(site()->MenuPrimary()) as $item): ?>
      
        <?php
          $active = null;
          $missing = null;
          $subnav = null;
          $classes = array();
          
          if (array_key_exists('uid', $item)) {
          
            // add active class
            if ($item['uid'] == $activeTop) {
              $active = true;
              $classes[] = 'active';
            }
            
            // add missing class
            if (!site()->page($item['uid'])) {
              $missing = true;
              $classes[] = 'missing';
            }
            
            // add subnav class
            if (array_key_exists('sub', $item) /*and $item['uid'] != $activeTop*/) {
              $subnav = true;
              $classes[] = 'hassubnav';
            }
            
            $url = site()->url() . '/' . $item['uid'];
          
          } elseif (array_key_exists('url', $item)) {
            $url = $item['url'];
          }
          
          // combine classes
          $class = ($active or $missing or $subnav) ? ' class="' . implode(' ', $classes) . '"' : '';
          
          // check subtitle
          $subtitle = (array_key_exists('subtitle', $item)) ? '<div class="subtitle">' . $item['subtitle'] . '</div>' : '';
        ?>
        
        <li<?php echo $class ?>>
          <a href="<?php echo $url ?>"><?php echo $item['title']?></a><?php echo $subtitle ?>
          
          <?php if (array_key_exists('sub', $item) /*and $item['uid'] != $activeTop*/): ?>
            <ul class="subnodes">
              <?php foreach ($item['sub'] as $subitem): ?>
                <?php
                  $href = (array_key_exists('uid', $subitem)) ? ' href="' . site()->url() . '/' . $subitem['uid'] . '"' : '';
                  $class = (site()->page($subitem['uid'])) ? '' : ' class="missing"';
                ?>
                <li<?php echo $class?>>
                  <a<?php echo $href?>><?php echo $subitem['title']?></a>
                </li>
              <?php endforeach ?>
            </ul>
          <?php endif ?>
        </li>
      <?php endforeach ?>
      
      <div class="backdrop"></div>
    </ul>
    
    <ul class="menu menu-secondary">
      

    
      <?php foreach (yaml(site()->MenuSecondary()) as $item): ?>
        <?php
          $title = $item['title'];
          $subtitle = (array_key_exists('subtitle', $item)) ? '<div class="subtitle">' . $item['subtitle'] . '</div>' : '';
          if (array_key_exists('url', $item)) {
            $url = $item['url'];
          } elseif (array_key_exists('uid', $item)) {
            $url = site()->url() . '/' . $item['uid'];
          } else {
            $url = null;
          }
          $url = ($url) ? ' href="' . $url . '"' : '';
        ?>
        <li>
          <a<?php echo $url ?>><?php echo $title ?></a><?php echo $subtitle ?>
        </li>
      <?php endforeach ?>
      
      
      <?php if (site()->setting('menu/search/enabled')): ?>
        <li class="search">
          <form class="search-container" action="<?php echo $site->url() . '/search'?>">
            <?php echo (new Asset('/acorn/assets/images/menu-search.svg'))->content() ?>
            <input id="search-box" type="text" class="search-box" name="q">
            <input type="submit" id="search-submit">
          </form>
        </li>
      <?php endif ?>
      
      <?php /*
      <li>
        <a id="settings-reading" data-modal="reading"><?php echo (new Asset('/acorn/assets/images/menu-font.svg'))->content() ?></a>
      </li>
      */ ?>
      
      <li>
        <a id="settings-reading" data-modal="reading">Aa</a>
      </li>
      
      <?php if ($user = $site->user()): ?>
        <button class="button-login" data-modal="account"><?php echo esc($user->firstName()) ?></button>        
      <?php else: ?>
        <button class="button-login" data-modal="login">Log in</button>
      <?php endif ?>
      
    </ul>
    
  </div>
</nav>

<?php if (hasSubMenu()): ?>
  
  <nav id="subnavigation">
    <div class="container">
      
      <ul class="menu">
        
        <?php if(preg_match_all('/(?<!#)#{2,3}([^#].*)\n/', $page->text(), $matches)): // Grabs H2's and H3's ?>
          <li id="toggle-toc">
            <a data-modal="toc"><?php echo (new Asset('/acorn/assets/images/menu-toc.svg'))->content() ?></a>
          </li>
        <?php endif ?>
        
        <?php foreach (submenuItems() as $item): ?>
          <?php
            
            $class = '';
            $url = '';
            
            if (array_key_exists('uid', $item)) {
              $url = ($site->page($item['uid'])) ? ' href="' . $site->url() . '/' . $item['uid'] . '"' : '';
              $classarray = array();
                if (!site()->page($item['uid'])) $classarray[] = 'missing';
                if ($item['uid'] == $activeSub)  $classarray[] = 'active';
              $class = (!empty($classarray)) ? ' class="' . implode(' ', $classarray) . '"' : '';
            } else {
              $class = ' class="missing"';
            }
          ?>
          <li<?php echo $class ?>>
            <a<?php echo $url ?>><?php echo $item['title'] ?></a>
          </li>
        <?php endforeach ?>
        
      </ul>
      
      <?php /*
      <ul class="menu menu-secondary">
        
        <li>
          <a id="settings-reading" data-modal="reading"><?php echo (new Asset('/acorn/assets/images/menu-font.svg'))->content() ?></a>
        </li>
      </ul>
      */ ?>
      
    </div>
  </nav>
<?php endif ?>







