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
<div class="container mt-3 sgs-search" id="small-group-search" x-data="smallGroupSearch()" x-cloak>

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

  <div x-show="isSearching && filteredList.length > 0">
    <small x-text="filteredList.length + ' groups match your search'"></small>
  </div>

  <div x-show="isSearching && filteredList.length === 0">
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

</div>
