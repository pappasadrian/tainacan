<template>
    <div 
            class="repository-level-page page-container">
        <tainacan-title 
                :bread-crumb-items="[
                    { path: $routerHelper.getAvailableImportersPath(), label: $i18n.get('importers') },
                    { path: '', label: importerType != undefined ? (importerName != undefined ? importerName :importerType) : $i18n.get('title_importer_page') }
                ]"/>
        <form   
                @click="formErrorMessage = ''"
                class="tainacan-form" 
                label-width="120px"
                v-if="importer != undefined && importer != null">
            <div 
                        v-if="importer.manual_collection || importer.accepts.file || importer.accepts.url"
                        class="columns">

                <div class="column">

                    <!-- File Source input -->
                    <b-field
                            v-if="importer.accepts.file"
                            :addons="false">
                        <label class="label">{{ $i18n.get('label_source_file') }}</label>
                        <help-button 
                                :title="$i18n.get('label_source_file')" 
                                :message="$i18n.get('info_source_file_upload')"/>
                        <br>
                        <b-upload
                                v-if="importer.tmp_file == undefined && (importerFile == undefined || importerFile == null || importerFile == '')" 
                                v-model="importerFile"
                                drag-drop
                                class="source-file-upload">
                            <section class="drop-inner">
                                <div class="content has-text-centered">
                                    <p>
                                        <span class="icon">
                                            <i class="tainacan-icon tainacan-icon-36px tainacan-icon-upload"/>
                                        </span>
                                    </p>
                                    <p>{{ $i18n.get('instruction_drop_file_or_click_to_upload') }}</p>
                                </div>
                            </section>
                        </b-upload>
                        <div 
                                class="control selected-source-file"
                                v-if="importerFile != undefined">
                            <span>{{ (importerFile.length != undefined && importerFile.length > 0 ) ? importerFile[0].name : importerFile.name }}</span>
                            <a 
                                    target="_blank"
                                    @click.prevent="importerFile = undefined">
                                <span 
                                        v-tooltip="{
                                            content: $i18n.get('remove_value'),
                                            autoHide: true,
                                            placement: 'bottom',
                                            classes: ['tainacan-tooltip', 'tooltip', 'repository-tooltip'],
                                        }"
                                        class="icon">
                                    <i class="tainacan-icon tainacan-icon-18px tainacan-icon-close"/>
                                </span>
                            </a>
                        </div>
                        <div 
                                class="control selected-source-file"
                                v-if="importerFile == undefined && importer.tmp_file">
                            <p>{{ $i18n.get('label_select_file') + ': ' + importer.tmp_file }}</p>
                        </div>
                    </b-field>

                    <!-- URL source input -------------------------------- --> 
                    <b-field 
                            v-if="importer.accepts.url"
                            :addons="false"
                            :label="$i18n.get('label_url_source_link')">
                        <help-button 
                                :title="$i18n.get('label_url_source_link')" 
                                :message="$i18n.get('info_url_source_link_helper')"/>
                        <b-input
                                id="tainacan-url-link-source"
                                v-model="url"/>  
                    </b-field>
                </div>

                <div 
                        v-if="importer.manual_collection"
                        style="margin-top: 2em;"
                        class="column is-narrow">
                    <span class="icon">
                        <i class="tainacan-icon tainacan-icon-pointer tainacan-icon-36px has-text-gray2" />
                    </span>
                </div>

                <div 
                        v-if="importer.manual_collection"
                        class="column">
                    <!-- Target collection selection -------------------------------- --> 
                    <b-field
                            :addons="false" 
                            :label="$i18n.get('label_target_collection')">
                        <help-button 
                                :title="$i18n.get('label_target_collection')" 
                                :message="$i18n.get('info_target_collection_helper')"/>
                        <br>
                        <div class="is-inline">
                            <b-select
                                    expanded
                                    id="tainacan-select-target-collection"
                                    :value="collectionId"
                                    @input="onSelectCollection($event)"
                                    :loading="isFetchingCollections"
                                    :placeholder="$i18n.get('instruction_select_a_target_collection')">
                                <option
                                        v-for="collection of collections"
                                        v-if="collection.current_user_can_edit_items"
                                        :key="collection.id"
                                        :value="collection.id">{{ collection.name }}
                                </option>
                            </b-select>
                            <router-link
                                    v-if="$userCaps.hasCapability('tnc_rep_edit_collections')"
                                    tag="a" 
                                    style="font-size: 0.875em;"
                                    class="add-link"     
                                    :to="{ path: $routerHelper.getNewCollectionPath(), query: { fromImporter: true }}">
                                <span class="icon">
                                    <i class="tainacan-icon tainacan-icon-add"/>
                                </span>
                                {{ $i18n.get('new_blank_collection') }}
                            </router-link>
                        </div>
                    </b-field>
                </div>
                        
            </div>

            <hr v-if="(importer.manual_collection || importer.accepts.file || importer.accepts.url) && (importer.options_form != undefined && importer.options_form != null && importer.options_form != '')">

            <div v-if="importer.options_form != undefined && importer.options_form != null && importer.options_form != ''">
                <!-- Importer custom options -->
                <form id="importerOptionsForm">
                    <div v-html="importer.options_form"/>
                </form>
            </div>

            <!-- Form submit -------------------------------- --> 
            <div class="columns is-gapless field is-grouped form-submit">
                <div class="control">
                    <button
                            id="button-cancel-importer-creation"
                            class="button is-outlined"
                            type="button"
                            @click="cancelBack">{{ $i18n.get('cancel') }}</button>
                </div>
                <span class="help is-danger">{{ formErrorMessage }}</span>
                <div 
                        v-if="!importer.manual_mapping"
                        class="control">
                    <button
                            :disabled="
                                    (formErrorMessage != undefined && formErrorMessage != '') ||
                                    sessionId == undefined || 
                                    importer == undefined || 
                                    (importer.manual_collection && collectionId == undefined) ||
                                    (importer.accepts.file && !importer.accepts.url && !importerFile) || 
                                    (!importer.accepts.file && importer.accepts.url && !url) ||
                                    (importer.accepts.file && importer.accepts.url && !importerFile && !url)"
                            id="button-submit-importer-creation"
                            @click.prevent="onFinishImporter()"
                            :class="{ 'is-loading': isLoadingRun, 'is-success': !isLoadingRun }"
                            class="button">{{ $i18n.get('run') }}</button>
                </div>
                <div 
                        v-if="importer.manual_mapping"
                        class="control">
                    <button
                            :disabled="
                                    (formErrorMessage != undefined && formErrorMessage != '') ||
                                    sessionId == undefined || 
                                    importer == undefined || 
                                    (importer.manual_collection && collectionId == undefined) ||
                                    (importer.accepts.file && !importer.accepts.url && !importerFile) || 
                                    (!importer.accepts.file && importer.accepts.url && !url) ||
                                    (importer.accepts.file && importer.accepts.url && !importerFile && !url)"
                            id="button-submit-collection-creation"
                            @click.prevent="onFinishImporter()"
                            :class="{ 'is-loading': isLoadingUpload, 'is-success': !isLoadingUpload }"
                            class="button">{{ $i18n.get('next') }}</button>
                </div>
            </div>
        </form>
        
        <b-loading 
                :active.sync="isLoading" 
                :can-cancel="false"/>
    </div>
</template>

<script>
import { mapActions } from 'vuex';

export default {
    name: 'ImporterEditionForm',
    data(){
        return {
            importerId: Number,
            importer: null,
            isLoading: false,
            isLoadingRun: false,
            isLoadingUpload: false,
            isFetchingCollections: false,
            form: {},
            formErrorMessage: '',
            mappedCollection: {
                'id': Number,
                'mapping': {},
                'total_items': Number
            },
            importerTypes: [],
            importerType: '',
            importerName: '',
            importerFile: null,
            importerSourceInfo: null,
            collections: [],
            collectionId: undefined,
            url: '',
            backgroundProcess: undefined
        }
    },
    created() {
        this.importerType = this.$route.params.importerSlug;
        this.collectionId = this.$route.query.targetCollection;
        this.sessionId = this.$route.params.sessionId;

        if (this.collectionId != undefined) {
            this.onSelectCollection(this.collectionId);
        }

        // Set importer's name
        this.fetchAvailableImporters().then((importerTypes) => {
           if (importerTypes[this.importerType]) 
            this.importerName = importerTypes[this.importerType].name;
        });

        if (this.sessionId != undefined)
            this.loadImporter();
        else
            this.createImporter();    
    },
    methods: {
        ...mapActions('importer', [
            'fetchAvailableImporters',
            'fetchImporter',
            'sendImporter',
            'updateImporter',
            'updateImporterFile',
            'updateImporterURL',
            'updateImporterOptions',
            'fetchImporterSourceInfo',
            'updateImporterCollection',
            'runImporter'
        ]),
        ...mapActions('collection', [
            'fetchAllCollectionNames'
        ]),
        createImporter() {
            // Puts loading on Draft Importer creation
            this.isLoading = true;

            // Creates draft Importer
            this.sendImporter(this.importerType)
                .then(res => {

                    this.sessionId = res.id;
                    this.importer = JSON.parse(JSON.stringify(res));

                    this.form = this.importer.options;
                    this.isLoading = false;

                    if (this.importer.manual_collection)
                        this.loadCollections();
                    
                })
                .catch(error => this.$console.error(error));
        },
        loadImporter() {
            // Puts loading on Draft Importer creation
            this.isLoading = true;

            // Creates draft Importer
            this.fetchImporter(this.sessionId)
                .then(res => {

                    this.sessionId = res.id;
                    this.importer = JSON.parse(JSON.stringify(res));

                    this.form = this.importer.options;
                    this.isLoading = false;

                    if (this.importer.manual_collection)
                        this.loadCollections();
                    
                })
                .catch(error => this.$console.error(error));
        },
        cancelBack(){
            this.$router.go(-1);
        },
        onUploadFile() {
            return new Promise((resolve, reject) => {
               this.updateImporterFile({ sessionId: this.sessionId, file: (this.importerFile && this.importerFile.length != undefined && this.importerFile.length > 0) ? this.importerFile[0] : this.importerFile})
                .then(updatedImporter => {    
                    this.importer = updatedImporter;
                    resolve();
                })
                .catch((errors) => {
                    this.formErrorMessage = errors.error_message;
                    this.$console.error(errors);
                    reject(errors);
                });
            });
        },
        onInputURL() {
            return new Promise((resolve, reject) => {
                this.updateImporterURL({ sessionId: this.sessionId, url: this.url })
                    .then(updatedImporter => {    
                        this.importer = updatedImporter;
                        resolve();
                    })
                    .catch((errors) => {
                        this.formErrorMessage = errors.error_message;
                        this.$console.error(errors);
                        reject(errors);
                    });
            });
        },
        onUpdateOptions() {
            return new Promise((resolve, reject) => {

                if (this.importer.options_form != undefined && this.importer.options != null && this.importer.options_form != '') {
                    let formElement = document.getElementById('importerOptionsForm');
                    let formData = new FormData(formElement);
                    let formObj = {};
                    
                    for (let [key, value] of formData.entries())
                        formObj[key] = value;

                    this.updateImporterOptions({ sessionId: this.sessionId, options: formObj })
                        .then(updatedImporter => {    
                            this.importer = updatedImporter;
                            resolve();
                        })
                        .catch((errors) => {
                            this.formErrorMessage = errors.error_message;
                            this.$console.log(errors);
                            reject(errors);
                        });

                } else {
                    resolve();
                }
            });
        },
        uploadSource() {
            this.isLoadingUpload = true;
            return new Promise((resolve, reject) => {
                if (this.importer.accepts.file && !this.importer.accepts.url) {
                    this.onUploadFile()
                        .then(() => { this.isLoadingUpload = false; resolve(); })
                        .catch((errors) => { this.isLoadingUpload = false; this.$console.error(errors) }); 
                } else if (!this.importer.accepts.file && this.importer.accepts.url) {
                    this.onInputURL()
                        .then(() => { this.isLoadingUpload = false; resolve() })
                        .catch((errors) => { this.isLoadingUpload = false; this.$console.log(errors); }); 
                } else if (this.importer.accepts.file && this.importer.accepts.url) {
                    if (this.importerFile) {
                        this.onUploadFile()
                            .then(() => { this.isLoadingUpload = false; resolve(); })
                            .catch((errors) => { this.isLoadingUpload = false; this.$console.log(errors) });
                    } else if (this.url) {
                        this.onInputURL()
                            .then(() => { this.isLoadingUpload = false; resolve() })
                            .catch((errors) => { this.isLoadingUpload = false; this.$console.log(errors); }); 
                    } else {
                        this.isLoadingUpload = false;
                        reject('No source file given');
                    }
                } else {
                    this.isLoadingUpload = false;
                    resolve();
                }
            });
        },
        onFinishImporter() {
            this.isLoadingRun = true;
            this.onUpdateOptions().then(() => {
                this.uploadSource()
                    .then(() => { 
                        if (this.importer.manual_mapping) {   
                            this.goToMappingPage();
                            this.isLoadingRun = false;
                        } else {
                            this.onRunImporter();
                        }
                    }).catch((errors) => {
                        this.isLoadingRun = false;
                        this.$console.log(errors);
                    });   
            })
            .catch((errors) => {
                this.isLoadingRun = false;
                this.$console.log(errors);
            });
          
        },
        onRunImporter() {
            this.runImporter(this.sessionId)
                .then(backgroundProcess => {  
                    this.backgroundProcess = backgroundProcess;
                    this.isLoadingRun = false;
                    this.$router.push(this.$routerHelper.getProcessesPage(backgroundProcess.bg_process_id));
                })
                .catch((errors) => {
                    this.isLoadingRun = false;
                    this.$console.log(errors);
                });
        },
        goToMappingPage() {
            this.$router.push(this.$routerHelper.getImporterMappingPath(this.importerType, this.sessionId, this.collectionId));
        },
        loadCollections() {
            // Generates options for target collection
            this.isFetchingCollections = true;
            this.fetchAllCollectionNames()
                .then((resp) => {
                    resp.request.then((collections) => {
                        this.collections = collections;
                        this.isFetchingCollections = false;
                    })
                    .catch((error) => {
                        this.$console.error(error);
                        this.isFetchingCollections = false;
                    }); 
                })
                .catch(() => {
                    this.isFetchingCollections = false;
                }); 
        },
        onSelectCollection(collectionId) {
            this.collectionId = collectionId;
            this.mappedCollection['id'] = collectionId;
        }
    }
}
</script>

<style lang="scss" scoped>

    @import "../../scss/_variables.scss";

    /deep/ .columns {
        padding-left: var(--tainacan-one-column);
        padding-right: var(--tainacan-one-column);
    }

    .field {
        position: relative;
    }

    .form-submit {
        margin-top: 24px;
    }

    .section-label {
        font-size: 1em !important;
        font-weight: 500 !important;
        color: var(--tainacan-blue5) !important;
        line-height: 1.2em;
    }

    .source-metadatum {
        padding: 2px 0;
        border-bottom: 1px solid var(--tainacan-gray2);
        width: 100%;
        margin-bottom: 6px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .is-inline .control{
        display: inline;
    }
    .drop-inner{
        padding: 0.25em 0.5em;
    }

    .mapping-header-label {
        color: var(--tainacan-info-color);
        margin: 12px 0 6px 0;
    }

    .tainacan-modal .animation-content {
        width: 100%;
        z-index: 99999;

        #metadatumEditForm {
            background-color: var(--tainacan-background-color);
        }
    }

    .source-file-upload {
        width: 100%;
        @include display-grid;
    }

    .selected-source-file {
        border: 1px solid var(--tainacan-gray2);
        padding: calc(0.375em - 1px) 10px !important;
        font-size: .875em;
        line-height: 1.5em;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    hr {
        margin: 0.5rem 0 1.5rem 0;
    }

</style>


