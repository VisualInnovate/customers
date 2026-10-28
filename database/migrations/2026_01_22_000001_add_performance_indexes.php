<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add index for chats table queries
        if (!$this->indexExists('chats', 'idx_chats_org_type_read')) {
            Schema::table('chats', function (Blueprint $table) {
                $table->index(['organization_id', 'type', 'is_read'], 'idx_chats_org_type_read');
            });
        }

        if (!$this->indexExists('chats', 'idx_chats_wam_id_org')) {
            Schema::table('chats', function (Blueprint $table) {
                $table->index(['wam_id', 'organization_id'], 'idx_chats_wam_id_org');
            });
        }

        // Add index for contacts table queries
        if (!$this->indexExists('contacts', 'idx_contacts_org_deleted')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->index(['organization_id', 'deleted_at'], 'idx_contacts_org_deleted');
            });
        }

        if (!$this->indexExists('contacts', 'idx_contacts_phone_org')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->index(['phone', 'organization_id'], 'idx_contacts_phone_org');
            });
        }

        if (!$this->indexExists('contacts', 'idx_contacts_latest_chat')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->index(['latest_chat_created_at'], 'idx_contacts_latest_chat');
            });
        }

        // Add index for campaign_logs table
        if (!$this->indexExists('campaign_logs', 'idx_campaign_logs_status_campaign')) {
            Schema::table('campaign_logs', function (Blueprint $table) {
                $table->index(['status', 'campaign_id'], 'idx_campaign_logs_status_campaign');
            });
        }

        if (!$this->indexExists('campaign_logs', 'idx_campaign_logs_campaign_status')) {
            Schema::table('campaign_logs', function (Blueprint $table) {
                $table->index(['campaign_id', 'status'], 'idx_campaign_logs_campaign_status');
            });
        }

        // Add index for organizations table
        if (!$this->indexExists('organizations', 'idx_organizations_identifier')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->index(['identifier'], 'idx_organizations_identifier');
            });
        }

        // Add index for templates table
        if (!$this->indexExists('templates', 'idx_templates_org_status')) {
            Schema::table('templates', function (Blueprint $table) {
                $table->index(['organization_id', 'status', 'deleted_at'], 'idx_templates_org_status');
            });
        }

        // Add index for chat_tickets table
        if (!$this->indexExists('chat_tickets', 'idx_chat_tickets_status')) {
            Schema::table('chat_tickets', function (Blueprint $table) {
                $table->index(['status'], 'idx_chat_tickets_status');
            });
        }

        if (!$this->indexExists('chat_tickets', 'idx_chat_tickets_assigned')) {
            Schema::table('chat_tickets', function (Blueprint $table) {
                $table->index(['assigned_to'], 'idx_chat_tickets_assigned');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            if ($this->indexExists('chats', 'idx_chats_org_type_read')) {
                $table->dropIndex('idx_chats_org_type_read');
            }
            if ($this->indexExists('chats', 'idx_chats_wam_id_org')) {
                $table->dropIndex('idx_chats_wam_id_org');
            }
        });

        Schema::table('contacts', function (Blueprint $table) {
            if ($this->indexExists('contacts', 'idx_contacts_org_deleted')) {
                $table->dropIndex('idx_contacts_org_deleted');
            }
            if ($this->indexExists('contacts', 'idx_contacts_phone_org')) {
                $table->dropIndex('idx_contacts_phone_org');
            }
            if ($this->indexExists('contacts', 'idx_contacts_latest_chat')) {
                $table->dropIndex('idx_contacts_latest_chat');
            }
        });

        Schema::table('campaign_logs', function (Blueprint $table) {
            if ($this->indexExists('campaign_logs', 'idx_campaign_logs_status_campaign')) {
                $table->dropIndex('idx_campaign_logs_status_campaign');
            }
            if ($this->indexExists('campaign_logs', 'idx_campaign_logs_campaign_status')) {
                $table->dropIndex('idx_campaign_logs_campaign_status');
            }
        });

        Schema::table('organizations', function (Blueprint $table) {
            if ($this->indexExists('organizations', 'idx_organizations_identifier')) {
                $table->dropIndex('idx_organizations_identifier');
            }
        });

        Schema::table('templates', function (Blueprint $table) {
            if ($this->indexExists('templates', 'idx_templates_org_status')) {
                $table->dropIndex('idx_templates_org_status');
            }
        });

        Schema::table('chat_tickets', function (Blueprint $table) {
            if ($this->indexExists('chat_tickets', 'idx_chat_tickets_status')) {
                $table->dropIndex('idx_chat_tickets_status');
            }
            if ($this->indexExists('chat_tickets', 'idx_chat_tickets_assigned')) {
                $table->dropIndex('idx_chat_tickets_assigned');
            }
        });
    }

    /**
     * Check if an index exists on a given table.
     */
    protected function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEXES FROM `{$table}` WHERE Key_name = ?", [$index]);
        return count($indexes) > 0;
    }
};

