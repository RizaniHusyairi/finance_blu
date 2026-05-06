import re
import sys

def replace_in_file(filepath):
    with open(filepath, 'r', encoding='latin1') as f:
        content = f.read()
    
    target = """    public function approve(Request $request, int $id)
    {
        $spp = DokumenSpp::with('workflowInstance.approvals')
            ->whereNotNull('tagihan_perjaldin_komponen_id')
            ->findOrFail($id);

        $instance = $spp->workflowInstance;
        abort_unless($instance, 404);

        $user = $request->user();
        if ($request->filled('approval_id')) {
            $approval = $instance->approvals->where('id', $request->input('approval_id'))->first();
            $roleCode = $approval ? $approval->role_code : $this->detectRoleCode($user);
        } else {
            $roleCode = $this->detectRoleCode($user);
            $approval = $instance->approvals->where('role_code', $roleCode)->first();
        }"""
        
    replacement = """    public function approve(Request $request, int $id)
    {
        $spp = DokumenSpp::with(['workflowInstance.approvals', 'standingInstruction'])
            ->whereNotNull('tagihan_perjaldin_komponen_id')
            ->findOrFail($id);

        $instance = $spp->workflowInstance;
        abort_unless($instance, 404);

        $user = $request->user();
        if ($request->filled('approval_id')) {
            $approval = $instance->approvals->where('id', $request->input('approval_id'))->first();
            $roleCode = $approval ? $approval->role_code : $this->detectRoleCode($user);
        } else {
            $roleCode = $this->detectRoleCode($user);
            $approval = $instance->approvals->where('role_code', $roleCode)->first();
        }

        if ($approval && $approval->role_code === 'PPK') {
            if (!$spp->standingInstruction || $spp->standingInstruction->status !== 'FINAL') {
                return back()->with('error', 'Standing Instruction wajib dibuat dan difinalkan sebelum PPK menyetujui SPP.');
            }
        }"""
        
    content = content.replace(target, replacement)
    
    with open(filepath, 'w', encoding='latin1') as f:
        f.write(content)
        
replace_in_file('c:/laragon/www/template_maxton/finance_aptp/app/Http/Controllers/SppPerjaldinVerifikasiController.php')
