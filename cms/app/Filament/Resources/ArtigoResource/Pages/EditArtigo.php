<?php

namespace App\Filament\Resources\ArtigoResource\Pages;

use App\Filament\Resources\ArtigoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArtigo extends EditRecord
{
    protected static string $resource = ArtigoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('previsualizar')
                ->label('Pré-visualizar')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->action(function () {
                    $data  = $this->form->getState();
                    $item  = array_merge($this->getRecord()->toArray(), $data);
                    foreach ($item as $k => $v) {
                        if ($v instanceof \DateTime) { $item[$k] = $v->format('Y-m-d'); }
                    }
                    file_put_contents('/tmp/paroquia_preview_in.json', json_encode($item));
                    exec('node /root/dev/paroquiansrjericopb/scripts/build-content.js --preview-stdin --type artigos 2>&1 < /tmp/paroquia_preview_in.json', $out, $exit);
                    if ($exit !== 0) {
                        \Filament\Notifications\Notification::make()->title('Erro ao gerar prévia')->body(implode("\n", $out))->danger()->send();
                        return;
                    }
                    $this->js("
                        document.querySelectorAll('.paroquia-preview-overlay').forEach(el => el.remove());
                        const o = document.createElement('div');
                        o.className = 'paroquia-preview-overlay';
                        o.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.75);display:flex;align-items:center;justify-content:center';
                        o.innerHTML = `<div style='width:93%;height:93%;background:#fff;border-radius:10px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.6)'><div style='display:flex;align-items:center;justify-content:space-between;padding:10px 18px;background:#1e3a5f;color:#fff;flex-shrink:0'><span style='font-weight:600'>Pré-visualização — dados do formulário (sem salvar)</span><button onclick=\"document.querySelectorAll('.paroquia-preview-overlay').forEach(el=>el.remove())\" style='background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;font-size:14px;cursor:pointer;border-radius:6px;padding:4px 14px'>✕ Fechar</button></div><iframe src='http://localhost:3000/preview.html?t=\${Date.now()}' style='flex:1;border:none;width:100%'></iframe></div>`;
                        o.addEventListener('click', e => { if (e.target === o) o.remove(); });
                        document.body.appendChild(o);
                    ");
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
