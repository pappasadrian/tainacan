<template>
    <div
            :style="style"
            :class="className + ' has-mounted'">
        <div
                v-if="showSearchBar"
                class="facets-search-bar"> 
            <button
                    :label="$root.__('Search', 'tainacan')"
                    class="search-button">
                <span class="icon">
                    <i>
                        <svg    
                                width="24"
                                height="24"
                                viewBox="-2 -2 20 20">
                            <path
                                    class="st0"
                                    d="M0,5.8C0,5,0.2,4.2,0.5,3.5s0.7-1.3,1.2-1.8s1.1-0.9,1.8-1.2C4.2,0.1,5,0,5.8,0S7.3,0.1,8,0.5
                                    c0.7,0.3,1.3,0.7,1.8,1.2s0.9,1.1,1.2,1.8c0.5,1.2,0.5,2.5,0.2,3.7c0,0.2-0.1,0.4-0.2,0.6c0,0.1-0.2,0.6-0.2,0.6
                                    c0.6,0.6,1.3,1.3,1.9,1.9c0.7,0.7,1.3,1.3,2,2c0,0,0.3,0.2,0.3,0.3c0,0.3-0.1,0.7-0.3,1c-0.2,0.6-0.8,1-1.4,1.2
                                    c-0.1,0-0.6,0.2-0.6,0.1c0,0-4.2-4.2-4.2-4.2c0,0-0.8,0.3-0.8,0.4c-1.3,0.4-2.8,0.5-4.1-0.1c-0.7-0.3-1.3-0.7-1.8-1.2
                                    C1.2,9.3,0.8,8.7,0.5,8S0,6.6,0,5.8z M1.6,5.8c0,0.4,0.1,0.9,0.2,1.3C2.1,8.2,3,9.2,4.1,9.6c0.5,0.2,1,0.3,1.6,0.3
                                    c0.6,0,1.1-0.1,1.6-0.3C8.7,9,9.7,7.6,9.8,6c0.1-1.5-0.6-3.1-2-3.9c-0.9-0.5-2-0.6-3-0.4C4.6,1.8,4.4,1.9,4.1,2
                                    c-0.5,0.2-1,0.5-1.4,0.9C2,3.7,1.6,4.7,1.6,5.8z"/>       
                        </svg>
                    </i>
                </span>
            </button>
            <input
                    :value="searchString"
                    @input="(value) => applySearchString(value)"
                    type="text">
        </div>
        <template v-if="isLoading">
            <ul
                    v-if="layout !== 'list'"
                    :style="{
                        gridGap: layout == 'grid' ? (gridMargin + 'px') : 'inherit',
                        marginTop: showSearchBar ? '1.5em' : '4px'
                    }"
                    class="facets-list"
                    :class="'facets-layout-' + layout + (!showName ? ' facets-list-without-margin' : '') + (maxColumnsCount ? ' max-columns-count-' + maxColumnsCount : '')">
                <li
                        :key="facet"
                        v-for="facet in Number(maxFacetsNumber)"
                        class="facet-list-item skeleton"
                        :style="{ 
                            marginBottom: layout == 'grid' && ((isMetadatumTypeRelationship || isMetadatumTypeTaxonomy) && showImage) ? (showName ? gridMargin + 12 : gridMargin) + 'px' : '',
                            minHeight: getSkeletonHeight()
                        }" />      
            </ul>
            <ul
                    v-else
                    :style="{
                        marginTop: showSearchBar ? '1.5em' : '4px'
                    }"
                    class="facets-list"
                    :class="'facets-layout-' + layout + (!showName ? ' facets-list-without-margin' : '') + (maxColumnsCount ? ' max-columns-count-' + maxColumnsCount : '')">
                <div
                        style="margin: 2px 6px" 
                        v-for="column in Number(maxColumnsCount)"
                        :key="column">
                    <li
                            v-for="facet in Math.ceil(maxFacetsNumber/maxColumnsCount)"
                            :key="facet"
                            class="facet-list-item skeleton"
                            :style="{ 
                                marginBottom: layout == 'grid' && ((isMetadatumTypeRelationship || isMetadatumTypeTaxonomy) && showImage) ? (showName ? gridMargin + 12 : gridMargin) + 'px' : '',
                                minHeight: getSkeletonHeight()
                            }" />    
                </div>  
            </ul>
        </template>
        <div v-else>
            <ul 
                    v-if="facets.length > 0 && layout != 'list'"
                    :style="{
                        gridGap: layout == 'grid' ? (gridMargin + 'px') : 'inherit',
                        marginTop: showSearchBar ? '1.5em' : '0px'
                    }"
                    class="facets-list"
                    :class="'facets-layout-' + layout + (maxColumnsCount ? ' max-columns-count-' + maxColumnsCount : '')">
                <facets-list-theme-unit
                        v-for="(facet, index) of facets"
                        :key="index"
                        :show-search-bar="showSearchBar"
                        :show-image="showImage"
                        :name-inside-image="nameInsideImage"
                        :child-facets-object="childFacetsObject"
                        :append-child-terms="appendChildTerms"
                        :facet="facet"
                        :cloud-rate="cloudRate"
                        :items-count-style="itemsCountStyle"
                        :tainacan-base-url="tainacanBaseUrl"
                        :layout="layout"
                        :metadatum-type="metadatumType"
                        :show-items-count="showItemsCount"
                        :is-loading-child-terms="isloadingChildTerms"
                        :link-term-facets-to-term-page="linkTermFacetsToTermPage"
                        :is-metadatum-type-taxonomy="isMetadatumTypeTaxonomy"
                        :is-metadatum-type-relationship="isMetadatumTypeRelationship"
                        @on-display-child-terms="displayChildTerms" />
            </ul>
            <ul 
                    v-if="facets.length > 0 && layout == 'list'"
                    :style="{
                        marginTop: showSearchBar ? '1.5em' : '0px'
                    }"
                    class="facets-list"
                    :class="'facets-layout-' + layout + (maxColumnsCount ? ' max-columns-count-' + maxColumnsCount : '')">
                <div 
                        v-for="column in Number(maxColumnsCount)"
                        :key="column">
                    <facets-list-theme-unit
                            v-for="(facet, index) of facets.slice((column - 1) * Math.ceil(facets.length/maxColumnsCount), column * Math.ceil(facets.length/maxColumnsCount))"
                            :key="index"
                            :show-search-bar="showSearchBar"
                            :show-image="showImage"
                            :name-inside-image="nameInsideImage"
                            :child-facets-object="childFacetsObject"
                            :append-child-terms="appendChildTerms"
                            :facet="facet"
                            :cloud-rate="cloudRate"
                            :items-count-style="itemsCountStyle"
                            :tainacan-base-url="tainacanBaseUrl"
                            :layout="layout"
                            :metadatum-type="metadatumType"
                            :show-items-count="showItemsCount"
                            :is-loading-child-terms="isloadingChildTerms"
                            :link-term-facets-to-term-page="linkTermFacetsToTermPage"
                            :is-metadatum-type-taxonomy="isMetadatumTypeTaxonomy"
                            :is-metadatum-type-relationship="isMetadatumTypeRelationship"
                            @on-display-child-terms="displayChildTerms" />
                </div>
            </ul>

            <button
                    v-if="showLoadMore && facets.length > 0 && (facets.length < totalFacets || lastTerm != '')"
                    @click="loadMore()"
                    class="show-more-button"
                    :label="$root.__('Show more', 'tainacan')">
                <span class="icon">
                    <i>
                        <svg
                                width="24"
                                height="24"
                                viewBox="4 3 24 24">
                            <path d="M 7.41,8.295 6,9.705 l 6,6 6,-6 -1.41,-1.41 -4.59,4.58 z"/>
                            <path
                                    d="M0 0h24v24H0z"
                                    fill="none"/>                        
                        </svg>
                    </i>
                </span>
            </button>
            <div
                    v-else
                    class="spinner-container"
                    :style="{ display: facets.length > 0 ? 'none' : 'flex'}">
                {{ $root.__('Nothing found.', 'tainacan') }}
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import qs from 'qs';
import debounce from 'lodash/debounce.js';

export default {
    name: "FacetsListTheme",
    props: {
        metadatumId: String,  
        metadatumType: String,  
        collectionId: String,  
        collectionSlug: String,
        parentTermId: String,
        showImage: Boolean,
        nameInsideImage: Boolean,
        showItemsCount: Boolean,
        showSearchBar: Boolean,
        showLoadMore: Boolean,
        appendChildTerms: Boolean,
        linkTermFacetsToTermPage: Boolean,
        itemsCountStyle: String,
        layout: String,
        cloudRate: Number,
        gridMargin: Number,
        maxFacetsNumber: Number,
        maxColumnsCount: Number,
        tainacanApiRoot: String,
        tainacanBaseUrl: String,
        tainacanSiteUrl: String,
        className: String,
        style: String
    },
    data() {
        return {
            facets: [],
            childFacetsObject: {},
            collection: undefined,
            facetsRequestSource: undefined,
            searchString: '',
            isLoading: false,
            isloadingChildTerms: null,
            isLoadingCollection: false,
            localMaxFacetsNumber: undefined,
            localOrder: undefined,
            tainacanAxios: undefined,
            offset: undefined,
            totalFacets: 0,
            lastTerm: undefined
        }
    },
    computed: {
        isMetadatumTypeRelationship() {
            return (this.metadatumType == 'Tainacan\\Metadata_Types\\Relationship') || (this.metadatumType == this.$root.__('Relationship', 'tainacan')) || (this.metadatumType == 'Relationship');
        },
        isMetadatumTypeTaxonomy() {
            return (this.metadatumType == 'Tainacan\\Metadata_Types\\Taxonomy') || (this.metadatumType == this.$root.__('Taxonomy', 'tainacan')) || (this.metadatumType == 'Taxonomy');
        }
    },
    created() {
        this.tainacanAxios = axios.create({ baseURL: this.tainacanApiRoot });
        
        if (tainacan_blocks && tainacan_blocks.nonce)
            this.tainacanAxios.defaults.headers.common['X-WP-Nonce'] = tainacan_blocks.nonce;

        this.offset = 0;
        this.fetchFacets();

        this.applySearchString = debounce(this.applySearchString, 750);
    },
    mounted() {
        this.$el.addEventListener('tainacan-blocks-facets-list-update', this.receiveSearchString);
    },
    beforeDestroy() {
        this.$el.removeEventListener('tainacan-blocks-facets-list-update', this.receiveSearchString);
    },
    methods: {
        receiveSearchString(event) {
            if (event.detail) {
                this.applySearchString({ target: { value: event.detail.searchString }});
            }
        },
        applySearchString(event) { 
            
            let value = event.target.value;
            
            if (this.searchString != value) {
                this.searchString = value;
                this.offset = 0;
                this.lastTerm = '';
                this.fetchFacets();
            }
        },
        loadMore() {
            this.offset += Number(this.maxFacetsNumber);
            this.fetchFacets();
        },
        fetchFacets() {
            if (this.offset == 0)
                this.facets = [];

            this.isLoading = true;
            
            if (this.facetsRequestSource != undefined && typeof this.facetsRequestSource == 'function')
                this.facetsRequestSource.cancel('Previous facets search canceled.');

            this.facetsRequestSource = axios.CancelToken.source();

            let endpoint = '/facets/' + this.metadatumId;
            let query = endpoint.split('?')[1];
            let queryObject = qs.parse(query);

            // Set up max facets to be shown
            if (this.maxFacetsNumber != undefined && Number(this.maxFacetsNumber) > 0)
                queryObject.number = this.maxFacetsNumber;
            else if (queryObject.number != undefined && queryObject.number > 0)
                this.localMaxFacetsNumber = queryObject.number;
            else {
                queryObject.number = 12;
                this.localMaxFacetsNumber = 12;
            }

            // Set up searching string
            if (this.searchString != undefined)
                queryObject.search = this.searchString;
            else if (queryObject.search != undefined)
                this.searchString = queryObject.search;
            else {
                delete queryObject.search;
                this.searchString = undefined;
            }

            // Set up paging
            queryObject.offset = this.offset;
            if (this.lastTerm != undefined)
                queryObject.last_term = this.lastTerm;

            // Set up parentTerm for taxonomies
            if (this.parentTermId !== undefined && this.parentTermId !== null && this.parentTermId !== '' && this.isMetadatumTypeTaxonomy)
                queryObject.parent = this.parentTermId;
            else {
                delete queryObject.parent;
                this.parentTermId = null;
            }

            // Parameter fo tech entity object with image and url
            queryObject['context'] = 'extended';

            endpoint = endpoint.split('?')[0] + '?' + qs.stringify(queryObject);
            
            this.tainacanAxios.get(endpoint, { cancelToken: this.facetsRequestSource.token })
                .then(response => {
                    
                    if (this.isMetadatumTypeTaxonomy) {
                        for (let facet of response.data.values) {
                            this.facets.push(Object.assign({ 
                                term_url: facet.entity && facet.entity.url ? facet.entity.url : this.tainacanSiteUrl + '/' + this.collectionSlug + '/#/?taxquery[0][compare]=IN&taxquery[0][taxonomy]=' + facet.taxonomy + '&taxquery[0][terms][0]=' + facet.value,
                                url: this.tainacanSiteUrl + '/' + this.collectionSlug + '/#/?taxquery[0][compare]=IN&taxquery[0][taxonomy]=' + facet.taxonomy + '&taxquery[0][terms][0]=' + facet.value
                            }, facet));
                        }
                    } else {
                        for (let facet of response.data.values) {
                            this.facets.push(Object.assign({ 
                                url: this.tainacanSiteUrl + '/' + this.collectionSlug + '/#/?metaquery[0][key]=' + this.metadatumId + '&metaquery[0][value]=' + facet.value
                            }, facet));
                        }
                    }
 
                    this.isLoading = false;
                    this.totalFacets = Number(response.headers['x-wp-total']);
                    this.lastTerm = response.data.values.length > 0 ? response.data.last_term.es_term : '';

                }).catch(() => { 
                    this.isLoading = false;
                    // console.log(error);
                });
        },
        fetchChildTerms(parentTermId) {
            
            this.isloadingChildTerms = parentTermId;

            let endpoint = '/facets/' + this.metadatumId;
            let query = endpoint.split('?')[1];
            let queryObject = qs.parse(query);

            // Set up max facets to be shown
            if (this.maxFacetsNumber != undefined && Number(this.maxFacetsNumber) > 0)
                queryObject.number = this.maxFacetsNumber;
            else if (queryObject.number != undefined && queryObject.number > 0)
                this.localMaxFacetsNumber = queryObject.number;
            else {
                queryObject.number = 12;
                this.localMaxFacetsNumber = 12;
            }

            // Set up searching string
            if (this.searchString != undefined)
                queryObject.search = this.searchString;
            else if (queryObject.search != undefined)
                this.searchString = queryObject.search;
            else {
                delete queryObject.search;
                this.searchString = undefined;
            }

            // Set up paging
            queryObject.offset = this.offset;
            if (this.lastTerm != undefined)
                queryObject.last_term = this.lastTerm;

            // Parameter fo tech entity object with image and url
            queryObject['context'] = 'extended';

            queryObject['parent'] = parentTermId
            endpoint = endpoint.split('?')[0] + '?' + qs.stringify(queryObject);
            
            this.tainacanAxios.get(endpoint, { cancelToken: this.facetsRequestSource.token })
                .then(response => {
                    let childFacets = [];
                    
                    for (let facet of response.data.values) {
                        childFacets.push(Object.assign({ 
                            term_url: facet.entity && facet.entity.url ? facet.entity.url : this.tainacanSiteUrl + '/' + this.collectionSlug + '/#/?taxquery[0][compare]=IN&taxquery[0][taxonomy]=' + facet.taxonomy + '&taxquery[0][terms][0]=' + facet.value,
                            url: this.tainacanSiteUrl + '/' + this.collectionSlug + '/#/?taxquery[0][compare]=IN&taxquery[0][taxonomy]=' + facet.taxonomy + '&taxquery[0][terms][0]=' + facet.value
                        }, facet));
                    }

                    this.$set(this.childFacetsObject, parentTermId, {
                        facets: childFacets,
                        visible: true
                    });
                    this.isloadingChildTerms = null;
                    
                }).catch(() => { 
                    this.isloadingChildTerms = null;
                    // console.log(error);
                });
        },
        displayChildTerms(parentTermId) {
            if (this.childFacetsObject[parentTermId]) {
                this.$set(this.childFacetsObject[parentTermId], 'visible', !this.childFacetsObject[parentTermId].visible);
            } else
                this.fetchChildTerms(parentTermId)
        },
        getSkeletonHeight() {
            switch(this.layout) {
                case 'grid':
                    if ((this.isMetadatumTypeRelationship || this.isMetadatumTypeTaxonomy) && this.showImage)
                        return '230px';
                    else
                        return '24px'
                case 'list':
                    if ((this.isMetadatumTypeRelationship || this.isMetadatumTypeTaxonomy) && this.showImage)
                        return '54px';
                    else
                        return '24px'
                default: return '54px';
            }
        }
    }
}
</script>

<style lang="scss">

    @import './style.scss';

</style>
