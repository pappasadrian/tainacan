<template>
<div class="tainacan-form tainacan-modal-content">
    <header class="tainacan-modal-title">
        <h2 v-if="capability.display_name != undefined">{{ $i18n.get('label_editing_capability') + ' ' }} <span class="has-text-bold">{{ capability.display_name }}</span></h2>
        <hr>
    </header>
    <div 
            v-if="!isLoading"
            class="capabilities-edit-form">  
        <div>
            <template v-if="existingRoles && Object.values(existingRoles).length && capability.roles">
                <b-field :addons="false">
                    <label class="label is-inline">
                        {{ $i18n.get('label_associated_roles') }}
                    </label>
                    <p>{{ $i18n.get('info_associated_roles') }}</p>
                    <br>
                    <div class="roles-list">
                        <b-checkbox
                                v-if="!capability.roles_inherited[role.slug]"
                                v-for="(role, roleIndex) of existingRoles"
                                :key="roleIndex"
                                :value="capability.roles[role.slug] || capability.roles_inherited[role.slug] ? true : false"
                                @input="($event) => updateRole(role.slug, $event)"
                                name="roles">
                            {{ role.name }}
                        </b-checkbox>
                    </div>
                </b-field>
            </template>
            <p 
                    v-else
                    class="is-italic has-text-gray">
                {{ $i18n.get('info_no_role_associated_capability') }}
            </p>
        </div>
        <div>
            <template v-if="existingRoles && Object.values(existingRoles).length && capability.roles">
                <b-field :addons="false">
                    <label class="label is-inline">
                        <i class="tainacan-icon tainacan-icon-1-25em tainacan-icon-alertcircle"/>
                        {{ $i18n.get('label_inherited_roles') }}
                        <help-button
                                :title="$i18n.get('label_inherited_roles')"
                                :message="$i18n.get('info_inherited_roles')"
                                forced-color="yellow2"/>
                    </label>
                    <br>
                    <div class="roles-list">
                        <b-checkbox
                                v-if="capability.roles_inherited[role.slug]"
                                v-for="(role, roleIndex) of existingRoles"
                                :key="roleIndex"
                                class="has-text-yellow2"
                                :value="capability.roles[role.slug] || capability.roles_inherited[role.slug] ? true : false"
                                name="roles_inherited"
                                disabled>
                            {{ role.name }}
                        </b-checkbox>
                    </div>
                </b-field>
            </template>
            <p 
                    v-else
                    class="is-italic has-text-gray">
                {{ $i18n.get('info_no_role_associated_capability') }}
            </p>
        </div>
    </div>
    <div v-else>
        <b-loading
                is-full-page="false" 
                :active.sync="isLoading" />
    </div>
    <div class="field is-grouped form-submit">
        <div class="control">
            <button
                    id="button-cancel-importer-edition"
                    class="button is-outlined"
                    type="button"
                    @click="$parent.close()">
                {{ $i18n.get('exit') }}</button>
        </div>
    </div>
</div>
</template>

<script>
import { mapActions } from 'vuex';

export default {
    name: 'CapabilityEditionModal',
    props: {
        capability: Object,
        capabilityKey: String
    },
    data() {
        return {
            existingRoles: [],
            isLoading: false
        }
    },
    created() {
        this.isLoading = true;
        this.fetchRoles().then((roles) => {
            this.existingRoles = roles;
            this.isLoading = false;
        });
    },
    methods: {
        ...mapActions('capability', [
            'fetchRoles',
            'addCapabilityToRole',
            'removeCapabilityFromRole'
        ]),
        updateRole(role, value) {
            if (value)
                this.addCapabilityToRole({ capabilityKey: this.capabilityKey.replace('%d', 'all'), role: role })
            else 
                this.removeCapabilityFromRole({ capabilityKey: this.capabilityKey.replace('%d', 'all'), role: role })
        }
    }
}
</script>

<style lang="scss" scoped>
 
    .capabilities-edit-form {
        min-height: 120px;
        vertical-align: top;
        display: flex;
        padding-top: 24px;

        &>div {
            padding: 24px;
            &:first-of-type {
                flex-grow: 3;
                flex-shrink: 1;
            }    
            &:last-of-type {
                background-color: var(--tainacan-yellow1);
                color: var(--tainacan-yellow1);

                .label,
                .control-label,
                .check {
                    color: var(--tainacan-yellow2);
                }
            }
        }
        .roles-list {
            height: 100%;
            column-count: 2;
            break-inside: avoid;

            label {
                margin-left: 12px;
            }
        }
        @media screen and (max-width: 1024px) {
            .roles-list {
                column-count: 1;
            }
        }
    }
</style>
