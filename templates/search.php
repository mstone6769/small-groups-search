<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<style>
[x-cloak] { display: none !important; }

.group-heading {
  display: flex;
  flex-direction: column-reverse;
}
.group-heading .group-target {
  font-family: Montserrat, sans-serif;
  color: #666;
  font-weight: 400;
  margin-bottom: 0;
  font-size: 1.25rem;
}
.group-name {
  margin-bottom: 0.5rem;
  font-size: 1.5rem;
  font-weight: 700;
  text-transform: none;
  letter-spacing: 0;
}
.life-group p { margin-bottom: 0; }
.life-group .group-description {
  font-style: italic;
  margin-bottom: 0.5rem;
}
.life-group .badge {
  font-family: Montserrat, sans-serif;
  background-color: #666;
  display: inline-block;
  padding: 0.35em 1em;
  font-size: 0.75rem;
  font-weight: 700;
  line-height: 1;
  color: #fff;
  text-align: center;
  white-space: nowrap;
  vertical-align: baseline;
  border-radius: 50rem;
}
.life-group .button {
  margin-bottom: 1rem;
  margin-top: 1rem;
}
.life-group .fa {
  min-width: 1.5rem;
  text-align: center;
}
.sgs-search .row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
}
.sgs-search .col-auto {
  margin-right: 1rem;
}
.sgs-search .mb-2 {
  margin-bottom: 0.5rem;
}
.sgs-search .mr-sm-2 {
  margin-right: 0.5rem;
}
.sgs-search .form-check label {
  font-size: 1rem;
}
.sgs-search input[type=search] {
  padding-left: 0.5rem;
  background-color: white;
}
.sgs-search select {
  color: #12181d;
  background-color: white;
  border: 1px solid #ccc;
  border-radius: 3px;
  padding: 3px;
  line-height: 32px;
  min-height: 32px;
}
</style>
<div class="container mt-3 sgs-search" id="small-group-search" x-data="smallGroupSearch()">

  <form class="row gy-2 gx-3 align-items-center">

    <div class="col-auto">
      <input type="search" x-model.trim="search" placeholder="Search" aria-label="Search" class="form-control mb-2 mr-sm-2">
    </div>

    <div class="col-auto">
      <select class="form-control mb-2" x-model="filterDays" @change="onSelectChange('Meets on', $event.target.value)">
        <option disabled value="">Meets on...</option>
        <template x-for="day in dayOptions" :key="day">
          <option :value="day" x-text="day"></option>
        </template>
      </select>
    </div>

    <div class="col-auto">
      <select class="form-control mb-2" x-model="filterDemographic" @change="onSelectChange('Demographic', $event.target.value)">
        <option disabled value="">Demographic...</option>
        <template x-for="demo in demographicOptions" :key="demo">
          <option :value="demo" x-text="demo"></option>
        </template>
      </select>
    </div>

    <div class="col-auto">
      <select class="form-control mb-2" x-model="filterType" @change="onSelectChange('Group Type', $event.target.value)">
        <option disabled value="">Type...</option>
        <template x-for="type in typeOptions" :key="type">
          <option :value="type" x-text="type"></option>
        </template>
      </select>
    </div>

    <div class="col-auto">
      <select class="form-control mb-2 mr-sm-2" x-model="filterCategory" @change="onSelectChange('Category', $event.target.value)" style="min-width: 187.5px">
        <option disabled value="">Category...</option>
        <template x-for="cat in categoryOptions" :key="cat">
          <option :value="cat" x-text="cat"></option>
        </template>
      </select>
    </div>

    <div class="col-auto">
      <div class="form-check mb-2 mr-sm-2">
        <input class="form-check-input" type="checkbox" x-model="childcare" id="childcareCheck"
               @change="onCheckboxChange('Childcare Available', childcare ? 'Yes' : 'No')">
        <label class="form-check-label" for="childcareCheck"> Childcare Available</label>
      </div>
    </div>

    <div class="col-auto">
      <div class="form-check mb-2 mr-sm-2">
        <input class="form-check-input" type="checkbox" x-model="online" id="onlineCheck"
               @change="onCheckboxChange('Online Zoom Group', online ? 'Yes' : 'No')">
        <label class="form-check-label" for="onlineCheck"> Online Zoom Group</label>
      </div>
    </div>

  </form>

  <div x-cloak x-show="isSearching && filteredList.length > 0">
    <small x-text="filteredList.length + ' groups match your search'"></small>
  </div>

  <div x-cloak x-show="isSearching && filteredList.length === 0">
    <p>Sorry, no groups match your search.</p>
  </div>

  <template x-for="group in filteredList" :key="group.name">
    <div class="life-group">
      <hr>
      <div class="group-heading">
        <h2 class="group-name">
          <a :href="group.formLink" @click.prevent="groupClick(group.name, group.formLink)" x-text="group.name"></a>
        </h2>
        <p class="group-target" x-text="group.target"></p>
      </div>
      <p class="group-description" x-text="group.description"></p>
      <p>
        <span class="sr-only">Leaders:</span>
        <i class="fa fa-address-card-o"></i>
        <span x-text="group.leaders"></span><span x-show="group.email"> | <a :href="group.email">Email</a></span><span x-show="group.phone"> | <a :href="group.phone">Phone</a></span>
      </p>
      <p>
        <span class="sr-only">Location:</span>
        <i class="fa fa-map-marker"></i>
        <span x-text="group.location"></span>
      </p>
      <p>
        <span class="sr-only">Meets on:</span>
        <i class="fa fa-calendar"></i>
        <span x-text="group.meetsOn"></span>
      </p>
      <p>
        <span x-show="group.childcareAvailable === 'Yes'" class="badge rounded-pill bg-dark">Childcare Available</span>
        <span x-show="group.online === 'Yes'" class="badge rounded-pill bg-dark">Online Zoom Group</span>
      </p>
      <a class="button" :href="group.formLink" @click.prevent="groupClick(group.name, group.formLink)">Join Group</a>
    </div>
  </template>

<?php
// Pre-render the initial (sorted, unfiltered) groups as static HTML so content is
// visible immediately — no layout shift waiting for Alpine to initialize.
// The x-init above removes this element after Alpine's first render pass.
$prerender_groups = $groups;
usort( $prerender_groups, fn( $a, $b ) => strcmp( $a['name'] ?? '', $b['name'] ?? '' ) );
?>
<div id="sgs-prerender">
<?php foreach ( $prerender_groups as $group ) :
    $name        = esc_html( $group['name']        ?? '' );
    $target      = esc_html( $group['target']      ?? '' );
    $description = esc_html( $group['description'] ?? '' );
    $leaders     = esc_html( $group['leaders']     ?? '' );
    $location    = esc_html( $group['location']    ?? '' );
    $meets_on    = esc_html( $group['meetsOn']     ?? '' );
    $form_link   = esc_url(  $group['formLink']    ?? '' );
    $raw_email   = $group['email'] ?? '';
    $email_href  = $raw_email ? 'mailto:' . esc_attr( $raw_email ) : '';
    $raw_phone   = $group['phone'] ?? '';
    $phone_href  = $raw_phone ? 'tel:+1' . preg_replace( '/\D/', '', $raw_phone ) : '';
    $childcare   = ( $group['childcareAvailable'] ?? '' ) === 'Yes';
    $online      = ( $group['online']             ?? '' ) === 'Yes';
?>
  <div class="life-group">
    <hr>
    <div class="group-heading">
      <h2 class="group-name">
        <a href="<?= $form_link ?>"><?= $name ?></a>
      </h2>
      <p class="group-target"><?= $target ?></p>
    </div>
    <p class="group-description"><?= $description ?></p>
    <p>
      <span class="sr-only">Leaders:</span>
      <i class="fa fa-address-card-o"></i>
      <?= $leaders ?><?php if ( $email_href ) : ?> | <a href="<?= $email_href ?>">Email</a><?php endif; ?><?php if ( $phone_href ) : ?> | <a href="<?= $phone_href ?>">Phone</a><?php endif; ?>
    </p>
    <p>
      <span class="sr-only">Location:</span>
      <i class="fa fa-map-marker"></i>
      <?= $location ?>
    </p>
    <p>
      <span class="sr-only">Meets on:</span>
      <i class="fa fa-calendar"></i>
      <?= $meets_on ?>
    </p>
    <p>
      <?php if ( $childcare ) : ?><span class="badge rounded-pill bg-dark">Childcare Available</span><?php endif; ?>
      <?php if ( $online ) : ?><span class="badge rounded-pill bg-dark">Online Zoom Group</span><?php endif; ?>
    </p>
    <a class="button" href="<?= $form_link ?>">Join Group</a>
  </div>
<?php endforeach; ?>
</div>

</div>
