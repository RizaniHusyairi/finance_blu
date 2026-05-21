import re
import sys

def replace_in_file(filepath):
    with open(filepath, 'r', encoding='latin1') as f:
        content = f.read()
    
    target = """        $sppModel = DokumenSpp::with(['tagihan', 'workflowInstances.approvals'])->findOrFail($spp_id);
        $this->ensureHonorSpp($sppModel);
        $instance = $sppModel->workflowInstances->sortByDesc('created_at')->first();
        
        $myApproval = $this->resolveApprovalForAction($instance, $roleCodes, $user, $request->input('approval_id'));
        if (!$myApproval || $myApproval->status !== 'PENDING') {
            return back()->with('error', 'Status verifikasi telah diproses atau tidak valid.');
        }"""
        
    replacement = """        $sppModel = DokumenSpp::with(['tagihan', 'workflowInstances.approvals', 'standingInstruction'])->findOrFail($spp_id);
        $this->ensureHonorSpp($sppModel);
        $instance = $sppModel->workflowInstances->sortByDesc('created_at')->first();
        
        $myApproval = $this->resolveApprovalForAction($instance, $roleCodes, $user, $request->input('approval_id'));
        if (!$myApproval || $myApproval->status !== 'PENDING') {
            return back()->with('error', 'Status verifikasi telah diproses atau tidak valid.');
        }

        if ($myApproval->role_code === 'PPK') {
            if (!$sppModel->standingInstruction || $sppModel->standingInstruction->status !== 'FINAL') {
                return back()->with('error', 'Standing Instruction wajib dibuat dan difinalkan sebelum PPK menyetujui SPP.');
            }
        }"""
        
    content = content.replace(target, replacement)
    
    with open(filepath, 'w', encoding='latin1') as f:
        f.write(content)
        
replace_in_file('c:/laragon/www/template_maxton/finance_aptp/app/Http/Controllers/VerifikasiSppHonorController.php')
