
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@anambaskab.go.id',
            'password' => Hash::make('password123'), // ganti setelah login pertama
            'role' => 'superadmin',
            'opd_name' => null,
        ]);
    }
}
