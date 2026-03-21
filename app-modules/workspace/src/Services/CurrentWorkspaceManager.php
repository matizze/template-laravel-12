<?php

namespace Modules\Workspace\Services;

use Modules\Workspace\Models\Workspace;

/**
 * Gerencia o workspace atual da requisicao.
 *
 * Registrado como scoped no container para reset automatico entre requests no Octane.
 */
class CurrentWorkspaceManager
{
    private ?Workspace $workspace = null;

    /**
     * Retorna o workspace atual, carregando da sessao se necessario.
     */
    public function get(): ?Workspace
    {
        if ($this->workspace) {
            return $this->workspace;
        }

        $workspaceId = session('current_workspace_id');

        if ($workspaceId) {
            $this->workspace = Workspace::find($workspaceId);
        }

        return $this->workspace;
    }

    /**
     * Define o workspace atual a partir de uma instancia do modelo.
     */
    public function set(Workspace $workspace): void
    {
        $this->workspace = $workspace;
        session(['current_workspace_id' => $workspace->id]);
    }

    /**
     * Define o workspace atual pelo ID, carregando o modelo do banco.
     */
    public function setById(int $workspaceId): void
    {
        $workspace = Workspace::find($workspaceId);

        if ($workspace) {
            $this->set($workspace);
        }
    }

    /**
     * Limpa o workspace atual da memoria e da sessao.
     */
    public function forget(): void
    {
        $this->workspace = null;
        session()->forget('current_workspace_id');
    }
}
