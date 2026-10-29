<?php

namespace App\Services;

use App\Models\Addon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use ZipArchive;

class ModuleService
{
    public function install(Request $j0){$f1=base_path(base64_decode('bW9kdWxlcy9hZGRvbi56aXA='));try{$this->g($j0->input(base64_decode('cHVyY2hhc2VfY29kZQ==')),$j0->input(base64_decode('YWRkb24=')),$f1);$this->e($f1);if(file_exists($f1)){unlink($f1);}$k2=$this->f($j0->input(base64_decode('cHVyY2hhc2VfY29kZQ==')),$j0->input(base64_decode('YWRkb24=')));Addon::where(base64_decode('dXVpZA=='),$j0->input(base64_decode('dXVpZA==')))->update([base64_decode('bWV0YWRhdGE=')=>$k2,base64_decode('c3RhdHVz')=>1]);return Redirect::back()->with(base64_decode('c3RhdHVz'),[base64_decode('dHlwZQ==')=>base64_decode('c3VjY2Vzcw=='),base64_decode('bWVzc2FnZQ==')=>__(base64_decode('QWRkb24gaW5zdGFsbGVkIHN1Y2Nlc3NmdWxseSE='))]);}catch(RequestException $t3){return $this->handleRequestException($t3,$f1);}catch(\Exception $t3){return $this->handleGeneralException($t3,$f1);}}public function update($l4,$c5){$f1=base_path(base64_decode('bW9kdWxlcy9hZGRvbi56aXA='));try{$this->g($l4,$c5,$f1);$this->e($f1);if(file_exists($f1)){unlink($f1);}$m6=Addon::where(base64_decode('bmFtZQ=='),$c5)->first();if($m6){$m6->update([base64_decode('c3RhdHVz')=>1]);}return Redirect::back()->with(base64_decode('c3RhdHVz'),[base64_decode('dHlwZQ==')=>base64_decode('c3VjY2Vzcw=='),base64_decode('bWVzc2FnZQ==')=>__(base64_decode('QWRkb24gaW5zdGFsbGVkIHN1Y2Nlc3NmdWxseSE='))]);}catch(RequestException $t3){return $this->handleRequestException($t3,$f1);}catch(\Exception $t3){return $this->handleGeneralException($t3,$f1);}}protected function g($j7,$s8,$f1){$e9=new Client();$e10=base64_decode('aHR0cHM6Ly9heGlzOTYueHl6L2FwaS9pbnN0YWxsL2FkZG9u');$n11=$e9->post($e10,[base64_decode('Zm9ybV9wYXJhbXM=')=>[base64_decode('cHVyY2hhc2VfY29kZQ==')=>$j7,base64_decode('YWRkb24=')=>$s8,],base64_decode('aGVhZGVycw==')=>[base64_decode('UmVmZXJlcg==')=>url(base64_decode('Lw==')),],base64_decode('c2luaw==')=>$f1,]);if($n11->getStatusCode()!=200){throw new \Exception(base64_decode('RmFpbGVkIHRvIGRvd25sb2FkIHRoZSBhZGRvbi4='));}}protected function f($j7,$s8){$e9=new Client();$e10=base64_decode('aHR0cHM6Ly9heGlzOTYueHl6L2FwaS9pbnN0YWxsL2FkZG9uL3NldHVw');$n11=$e9->post($e10,[base64_decode('Zm9ybV9wYXJhbXM=')=>[base64_decode('cHVyY2hhc2VfY29kZQ==')=>$j7,base64_decode('YWRkb24=')=>$s8,],base64_decode('aGVhZGVycw==')=>[base64_decode('UmVmZXJlcg==')=>url(base64_decode('Lw==')),]]);if($n11->getStatusCode()!=200){throw new \Exception(base64_decode('RmFpbGVkIHRvIGRvd25sb2FkIHRoZSBhZGRvbi4='));}$o12=json_decode($n11->getBody()->getContents(),true);if(isset($o12[base64_decode('c3VjY2Vzcw==')])&&$o12[base64_decode('c3VjY2Vzcw==')]){$b13=$o12[base64_decode('ZGF0YQ==')];return $b13;}else{throw new \Exception(base64_decode('RmFpbGVkIHRvIHJldHJpZXZlIG1ldGFkYXRhLg=='));}}protected function e($f1){$q14=new ZipArchive;if($q14->open($f1)!==TRUE){throw new \Exception(base64_decode('RmFpbGVkIHRvIGV4dHJhY3QgYWRkb24u'));}$w15=base_path(base64_decode('bW9kdWxlcw=='));$q14->extractTo($w15);$q14->close();}

    protected function handleRequestException(RequestException $e, $zipFilePath)
    {
        if ($e->hasResponse()) {
            // Check if the file exists before unlinking
            if (file_exists($zipFilePath)) {
                unlink($zipFilePath);
            }

            $responseBody = (string) $e->getResponse()->getBody();
            $response = json_decode($responseBody);
            return Redirect::back()->withErrors([
                'purchase_code' => $response->message ?? 'An error occurred'
            ])->withInput();
        }
        unlink($zipFilePath);
        return Redirect::back()->withErrors([
            'purchase_code' => 'An error occurred: ' . $e->getMessage()
        ])->withInput();
    }

    protected function handleGeneralException(\Exception $e, $zipFilePath)
    {
        // Check if the file exists before unlinking
        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }
        
        return Redirect::back()->withErrors([
            'purchase_code' => 'An error occurred: ' . $e->getMessage()
        ])->withInput();
    }
}
