<template>
<div 
        style="width: 100%;">
    <div
            class="term-item"
            :class="{
                'opened-term': term.opened
            }">
        <span 
                class="term-name" 
                :class="{'is-danger': formWithErrors == term.id }"
                v-html="term.hierarchy_path ? (`<span class='term-name-hierarchy-path'>${term.hierarchy_path}</span>${term.name}`) : term.name" />
        <span   
                v-if="term.id != undefined"
                class="label-details">
            <span 
                    class="not-saved" 
                    v-if="term.id == 'new'"> 
                {{ $i18n.get('info_not_saved') }}
            </span>
        </span>
        <span 
                v-if="currentUserCanEditTaxonomy"
                class="controls" 
                :class="{'is-disabled': isEditingTerm}">
            <a
                    @click.prevent="editTerm()">
                <span
                        v-tooltip="{
                            content: $i18n.get('edit'),
                            autoHide: true,
                            placement: 'auto'
                        }"
                        class="icon">
                    <i class="tainacan-icon tainacan-icon-1-25em tainacan-icon-edit"/>
                </span>
            </a>
            <a @click.prevent="tryToRemoveTerm()">
                <span
                        v-tooltip="{
                            content: $i18n.get('delete'),
                            autoHide: true,
                            placement: 'auto'
                        }"
                        class="icon">
                    <i class="tainacan-icon tainacan-icon-1-25em tainacan-icon-delete"/>
                </span>
            </a>
        </span>
    </div>
</div>
</template>

<script>
import { mapActions } from 'vuex';
import CustomDialog from '../other/custom-dialog.vue';

export default {
    name: 'RecursiveTermItem',
    props: {
        term: Object,
        index: Number,
        taxonomyId: Number,
        order: String,
        currentUserCanEditTaxonomy: Boolean
    },
    data(){
        return {
            isLoadingTerms: false,
            isEditingTerm: false
        }
    },
    created() {
        this.$eventBusTermsList.$on('editTerm', this.eventOnEditTerm);
        this.$eventBusTermsList.$on('termEditionSaved', this.eventOnTermEditionSaved);
        this.$eventBusTermsList.$on('termEditionCanceled', this.eventOnTermEditionCanceled);        
    },
    beforeDestroy() {
        this.$eventBusTermsList.$off('editTerm', this.eventOnEditTerm);
        this.$eventBusTermsList.$off('termEditionSaved', this.eventOnTermEditionSaved);
        this.$eventBusTermsList.$off('termEditionCanceled', this.eventOnTermEditionCanceled);                
    },
    methods: {
        ...mapActions('taxonomy', [
            'updateTerm',
            'deleteTerm'
        ]),
        editTerm() {        
            this.term.opened = !this.term.opened;
            
            this.$eventBusTermsList.onEditTerm(this.term);
        },
        tryToRemoveTerm() {

            // Checks if user is deleting a term with unsaved info.
            if (this.term.id == 'new') {
                this.$buefy.modal.open({
                    parent: this,
                    component: CustomDialog,
                    props: {
                        icon: 'alert',
                        title: this.$i18n.get('label_warning'),
                        message: this.$i18n.get('info_warning_terms_not_saved'),
                        onConfirm: () => { this.removeTerm(); },
                    },
                    trapFocus: true,
                    customClass: 'tainacan-modal',
                    closeButtonAriaLabel: this.$i18n.get('close')
                });  
            } else {
                this.removeTerm();
            }

        },
        removeTerm() {

            this.$buefy.modal.open({
                parent: this,
                component: CustomDialog,
                props: {
                    icon: 'alert',
                    title: this.$i18n.get('label_warning'),
                    message: this.$i18n.get('info_warning_selected_term_delete'),
                    onConfirm: () => { 
                        // If all checks passed, term can be deleted
                        this.$eventBusTermsList.onDeleteBasicTermItem(this.term);
                    }                
                },
                trapFocus: true,
                customClass: 'tainacan-modal',
                closeButtonAriaLabel: this.$i18n.get('close')
            });  
        },
        eventOnEditTerm() {
            this.isEditingTerm = true;
        },
        eventOnTermEditionSaved() {
            this.isEditingTerm = false;
            this.term.opened = false;
        },
        eventOnTermEditionCanceled() {
            this.isEditingTerm = false;
            this.term.opened = false;
        }
    }
}
</script>

<style lang="scss" scoped>

    // Term Item
    .term-item {
        padding: 0 0 0 1.75em;
        min-height: 2.5em;
        display: flex; 
        position: relative;
        align-items: center;
        justify-content: space-between;
        border-left: 1px solid transparent;
        visibility: visible;
        opacity: 1;
        transition: display 0.3s, visibility 0.3s, opacity 0.3s;
        width: 100%;

        &:first-child:hover {
            background-color: var(--tainacan-gray1) !important;
            .controls {
                visibility: visible;
                opacity: 1.0;
            }
            &::before {
                border-color: transparent transparent transparent var(--tainacan-gray2) !important;
            }
        }

        .term-name {
            text-overflow: ellipsis;
            overflow-x: hidden;
            white-space: nowrap;
            margin-left: 0.4em;
            margin-right: 0.4em;
            display: inline-block;
            max-width: 73%;
            color: var(--tainacan-gray5);

            &.is-danger {
                color: var(--tainacan-danger) !important;
            }
        }
        /deep/ .term-name-hierarchy-path {
            color: var(--tainacan-gray-4);
        }
        .label-details {
            font-weight: normal;
            color: var(--tainacan-gray3);
            margin-right: auto;
        }
        .not-saved {
            font-style: italic;
            font-weight: bold;
            color: var(--tainacan-danger);
        }
        .controls {
            height:  3.125em; 
            visibility: hidden;
            opacity: 0.0;
            display: flex;
            justify-content: space-between;
            background-color: var(--tainacan-gray2);
            padding: 0.65em 0.875em;

            a {
                display: flex;
                align-items: center;
                margin: 0 0.375em;
                .icon {
                    bottom: 1px;   
                    position: relative;
                    i, i:before { font-size: 1.25em; }
                }
            }            
        }
        .controls.is-disabled a {
            color: var(--tainacan-info-color) !important;
            cursor: not-allowed !important;
            user-select: none;
        }

        &.opened-term:first-child {
            cursor: default;
            background-color: var(--tainacan-blue1);

            &:before {
                content: '';
                display: block;
                position: absolute;
                left: 100%;
                right: -20px;
                width: 0;
                height: 0;
                border-style: solid;
                border-color: transparent transparent transparent var(--tainacan-blue1);
                border-left-width: 24px;
                border-top-width: 1.55em;
                border-bottom-width: 1.55em;
                top: 0;
            }
            &:hover:before {
                border-color: transparent transparent transparent var(--tainacan-gray1);
            }
        }

        &.collapsed-term {
            display: none;
            visibility: hidden;
            opacity: 0;
            transition: display 0.3s, visibility 0.3s, opacity 0.3s;
        }
    }
    .view-more-terms {
        font-size: 0.875em;
        margin: 0 0 0 1.75em !important;
        padding: 0.5em 0 0.5em 1.75em;
        display: flex;
        border-top: 1px solid var(--tainacan-gray1);
    }
</style>
